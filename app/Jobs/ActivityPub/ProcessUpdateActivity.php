<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type\Extended\Activity\Update;
use ActivityPhp\Type\Extended\Object\Article;
use ActivityPhp\Type\Extended\Object\Mention;
use App\Domain\ActivityPub\Mastodon\Document;
use App\Domain\ActivityPub\Mastodon\Note;
use App\Domain\ActivityPub\Mastodon\RsaSignature2017;
use App\Events\LocalActorMentioned;
use App\Events\LocalNoteReplied;
use App\Exceptions\LocalIdException;
use App\Exceptions\SignatureVerificationException;
use App\Models\ActivityPub\Activity;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\Note as ActivityPubNote;
use App\Models\ActivityPub\RemoteNote;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webmozart\Assert\Assert;

final class ProcessUpdateActivity implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
         * Create a new job instance.
         *
         * @return void
         */
    public function __construct(protected Actor $activityActor, protected readonly Update $action)
    {
        Context::add('actor', $this->activityActor->id);
        Context::add('type', $this->action->type);

        // Supported objects
        $supportedObjects = [
            Article::class,
            Document::class,
            Note::class,
        ];

        Assert::isInstanceOfAny($this->action->object, $supportedObjects);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            // Verify linked data signature
            $this->verifySignature();
        } catch (SignatureVerificationException $e) {
            Log::error($e->getMessage(), ['action' => $this->action->toArray()]);
        }

        DB::beginTransaction();

        // Store the object
        /** @var \ActivityPhp\Type\Extended\Object\Article|\App\Domain\ActivityPub\Mastodon\Document|\App\Domain\ActivityPub\Mastodon\Note $object */
        $object = $this->action->object;

        /** @var \App\Models\ActivityPub\Note $note */
        $note = ActivityPubNote::firstOrNew(['activityId' => $object->id]);
        if ($note->exists === false) {
            $note->note_type = $this->activityActor->actor_type;
        }
        $note->actor_id = $this->activityActor->id;

        if ($object->published !== null) {
            try {
                $note->published_at = Carbon::parse($object->published);
            } catch (InvalidFormatException) {
            }
        }

        $note->content = data_get($object, 'content');
        $note->contentMap = $object->contentMap;
        $note->summary = is_string($object->summary) ? $object->summary : json_encode($object->summary, JSON_THROW_ON_ERROR);
        $note->sensitive = data_get($object, 'sensitive', false);
        $note->to = $object->to !== null ? Arr::wrap($object->to) : [];
        $note->cc = $object->cc !== null ? Arr::wrap($object->cc) : null;
        if (!empty($object->inReplyTo)) {
            $note->inReplyTo = is_string($object->inReplyTo) ? $object->inReplyTo : json_encode($object->inReplyTo, JSON_THROW_ON_ERROR);
            // Store the id if available
            // TODO: add support for inReplyTo in array form
            if (is_string($object->inReplyTo)) {
                try {
                    $replyTo = LocalNote::byActivityId($object->inReplyTo)->firstOrFail();
                    $note->replyTo_id = $replyTo->id;
                    $note->setRelation('replyingTo', $replyTo);
                } catch (ModelNotFoundException|LocalIdException) {
                    try {
                        $replyTo = RemoteNote::byActivityId($object->inReplyTo)->firstOrFail();
                        $note->replyTo_id = $replyTo->id;
                        $note->setRelation('replyingTo', $replyTo);
                    } catch (ModelNotFoundException) {
                    }
                }
            }
        }
        // Default to 'Note' if no type exists
        $note->type = data_get($object, 'type', 'Note');
        $note->save();

        // Store the activity
        /** @var \App\Models\ActivityPub\ActivityCreate $activityModel */
        $activityModel = Activity::updateOrCreate([
            'activityId' => $this->action->get('id'),
            'type' => $this->action->type,
        ], [
            'object' => $this->action->toArray(),
            'object_type' => data_get($this->action, 'object.type'),
            'target_id' => $note->id,
            'actor_id' => $this->activityActor->id,
        ]);

        // Make sure it hasn't been already processed
        if ($activityModel->accepted) {
            Log::info('Model already processed and accepted, ignoring');
            DB::rollBack();
            return;
        }

        DB::commit();

        $localRecipients = $this->getLocalRecipients();

        collect(Arr::wrap($object->tag))->where('type', 'Mention')
            ->map(fn (Mention $mention) : ?LocalActor => LocalActor::where('activityId', $mention->href)->first())
            ->filter()
            ->each(function (LocalActor $actor) use ($note) : void {
                $actor->mentions()->attach($note);
                if ($note->replyingTo instanceof LocalNote && $note->replyingTo->actor_id === $actor->id) {
                    LocalNoteReplied::dispatch($note, $note->replyingTo);
                } else {
                    LocalActorMentioned::dispatch($actor, $note);
                }
            });

        // TODO: Forward inbox activities, see https://www.w3.org/TR/activitypub/#inbox-forwarding
        //

        // If nobody is mentioned, no need to accept anything
        if ($localRecipients->isEmpty()) {
            return;
        }
        // Accept the creation, any of the receivers can sign the accept in the name of the instance
        SendCreateAcceptToActor::dispatch($localRecipients->first(), $activityModel);
    }

    /**
     *
     * @return void
     * @see https://docs.joinmastodon.org/spec/security/#ld-verify
     */
    private function verifySignature(): void
    {
        // No signature!!
        if (empty($this->action->signature)) {
            throw new SignatureVerificationException('No signature available on object');
        }

        // Wrong signature type, mastodon-specific
        if (!$this->action->signature instanceof RsaSignature2017) {
            throw new SignatureVerificationException('Signature is not RsaSignature2017');
        }

        $actor = $this->action->actor;
        if (!is_string($actor)) {
            throw new SignatureVerificationException('unsupported actor type: ' . json_encode($actor));
        }

        // Signature's creator and actor don't match
        if (mb_strpos($this->action->signature->creator, $actor) !== 0) {
            throw new SignatureVerificationException("Signature actor and activity actor don't match: \n
                {$this->action->signature->creator} != {$actor}");
        }

        // Sender an author match
        if ($this->action->actor !== data_get($this->action, 'object.attributedTo')) {
            throw new SignatureVerificationException('Actor and object attribution don\'t match');
        }

        // Actor should have been retrieved on middleware so if real, it should exists on the DB
        try {
            $this->activityActor = Actor::where('activityId', $this->action->actor)->firstOrFail();
        } catch (ModelNotFoundException) {
            throw new SignatureVerificationException('Could not find the actor on the DB');
        }

        $array = $this->action->toArray();
        unset($array['signature']['id'], $array['signature']['type'], $array['signature']['signatureValue']);

        // TODO: Base64-decode the signatureValue and verify it against the public key in signature[creator].
    }

    /**
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\LocalActor>
     */
    private function getLocalRecipients() : Collection
    {

        $recipients = array_merge(
            Arr::wrap($this->action->to),
            Arr::wrap($this->action->cc),
            Arr::wrap($this->action->audience),
            Arr::wrap($this->action->bto),
            Arr::wrap($this->action->bcc)
        );

        // Let's look for local actors who are followers
        if (in_array($this->activityActor->followers_url, $recipients)) {
            $recipients = array_merge(
                $recipients,
                $this->activityActor
                    ->followers()
                    ->pluck('actors.activityId')
                    ->toArray()
            );
        }
        return LocalActor::whereIn('activityId', array_unique($recipients))->get();
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return $this->action->id;
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'instance-origin:' . $this->activityActor->domain,
            'update',
            'federation-in',
        ];
    }
}
