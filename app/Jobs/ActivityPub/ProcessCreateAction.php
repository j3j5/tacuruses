<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type\Extended\Object\Article;
use App\Domain\ActivityPub\Mastodon\Create;
use App\Domain\ActivityPub\Mastodon\Document;
use App\Domain\ActivityPub\Mastodon\Note;
use App\Domain\ActivityPub\Mastodon\RsaSignature2017;
use App\Events\LocalActorMentioned;
use App\Models\ActivityPub\Activity;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\LocalActor;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

use function Safe\json_encode;
use function Safe\preg_match;

final class ProcessCreateAction implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected Actor $activityActor, protected readonly Create $action)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Verify linked data signature
            $this->verifySignature();
        } catch(RuntimeException $e) {
            Log::error($e->getMessage(), ['action' => $this->action->toArray()]);
        }

        DB::beginTransaction();

        // Store the object
        $object = $this->action->object;

        // Supported objects
        $supportedObjects = [
            Article::class,
            Document::class,
            Note::class,
        ];

        if (empty($object) || is_string($object)) {
            DB::rollBack();
            throw new RuntimeException("'$object'  is an unsupported type");
        }

        if (!in_array(get_class($object), $supportedObjects)) {
            DB::rollBack();
            throw new RuntimeException(get_class($object) . ' is an unsupported object');
        }
        /** @var \ActivityPhp\Type\Extended\Object\Article|\App\Domain\ActivityPub\Mastodon\Document|\App\Domain\ActivityPub\Mastodon\Note $object */
        $note = RemoteNote::firstOrNew(['activityId' => $object->id]);
        $note->actor_id = $this->activityActor->id;
        if ($object->published !== null) {
            try {
                $note->published_at = Carbon::parse($object->published);
            } catch (InvalidFormatException) {
            }
        }

        $note->content = data_get($object, 'content', '');
        $note->contentMap = $object->contentMap;
        $note->summary = is_string($object->summary) ? $object->summary : json_encode($object->summary);
        $note->sensitive = data_get($object, 'sensitive', false);
        $note->to = $object->to !== null ? Arr::wrap($object->to) : [];
        $note->cc = $object->to !== null ? Arr::wrap($object->cc) : null;
        if (!empty($object->inReplyTo)) {
            $note->inReplyTo = is_string($object->inReplyTo) ? $object->inReplyTo : json_encode($object->inReplyTo);
            // Store the id if available
            // TODO: add support for inReplyTo in array form
            if (is_string($object->inReplyTo)) {
                try {
                    $replyTo = ActivityPubNote::where('activityId', $object->inReplyTo)->firstOrFail();
                    $note->replyTo_id = $replyTo->id;
                } catch (ModelNotFoundException) {
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

        // Attach the mention to all local recipients
        $localRecipients->each(function (LocalActor $actor) use ($note) : void {
            $actor->mentions()->attach($note);
            LocalActorMentioned::dispatch($actor, $note);
        });

        if ($localRecipients->isEmpty()) {
            return;
        }

        // TODO: Forward inbox activities, see https://www.w3.org/TR/activitypub/#inbox-forwarding
        //

        // Accept the creation, any of the receivers can sign the accept in the name of the instance
        SendCreateAcceptToActor::dispatch($localRecipients->first(), $activityModel); /** @phpstan-ignore-line */
    }

    /**
     *
     * @return void
     * @see https://docs.joinmastodon.org/spec/security/#ld-verify
     */
    private function verifySignature()
    {
        // No signature!!
        if (empty($this->action->signature)) {
            throw new RuntimeException('No signature available on object');
        }

        // Wrong signature type, mastodon-specific
        if (!$this->action->signature instanceof RsaSignature2017) {
            throw new RuntimeException('Signature is not RsaSignature2017');
        }

        $actor = $this->action->actor;
        if (!is_string($actor)) {
            throw new RuntimeException('unsupported actor type: ' . json_encode($actor));
        }

        // Signature's creator and actor don't match
        if (mb_strpos($this->action->signature->creator, $actor) !== 0) {
            throw new RuntimeException("Signature actor and activity actor don't match: \n
                {$this->action->signature->creator} != {$actor}");
        }

        // Sender an author match
        if ($this->action->actor !== data_get($this->action, 'object.attributedTo')) {
            throw new RuntimeException('Actor and object attribution don\'t match');
        }

        // Actor should have been retrieved on middleware so if real, it should exists on the DB
        try {
            $this->activityActor = Actor::where('activityId', $this->action->actor)->firstOrFail();
        } catch (ModelNotFoundException) {
            throw new RuntimeException('Could not find the actor on the DB');
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

        $localRecipients = collect($recipients)
            ->filter(fn (string $recipient) : bool => mb_strpos($recipient, config('app.url')) === 0)
            ->map(function (string $recipient) : string {
                if (!preg_match(LocalActor::USER_REGEX, $recipient, $matches)) {
                    throw new RuntimeException('Unknow local user on our own domain');
                }
                return $matches['user'];
            });

        return LocalActor::whereIn('username', $localRecipients)->get();
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
}
