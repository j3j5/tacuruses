<?php

namespace App\Jobs\ActivityPub;

use App\Domain\ActivityPub\Mastodon\Create;
use App\Domain\ActivityPub\Mastodon\Note;
use App\Domain\ActivityPub\Mastodon\RsaSignature2017;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\RemoteNote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ProcessCreateAction implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Actor $activityActor;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected readonly Create $action)
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
            Log::error($e->getMessage());
        }
        // Store the object
        $object = $this->action->object;
        if (!$object instanceof Note) {
            throw new RuntimeException('Unsupported object');
        }
        // TODO: validate or create manually
        $note = new RemoteNote();
        $note->actor_id = $this->activityActor->id;
        $note->published_at = $object->published;
        $note->content = $object->content;
        $note->contentMap = $object->contentMap;
        $note->summary = $object->summary;
        $note->sensitive = $object->sensitive;
        $note->to = $object->to;
        $note->cc = $object->cc;
        $note->inReplyTo = $object->inReplyTo;
        // Get all local recipients
        $this->getLocalRecipients()->each(function (LocalActor $actor) {
        });
        // Process activity for all the local recipients (add replies or mentions)
        // Forward inbox activities, see https://www.w3.org/TR/activitypub/#inbox-forwarding
        //
    }

    /**
     *
     * @return void
     * @see https://docs.joinmastodon.org/spec/security/#ld-verify
     */
    private function verifySignature()
    {
        // No signature!!
        if (data_get($this->action, 'signature', false)) {
            throw new RuntimeException('No signature available on object');
        }
        // Wrong signature type, mastodon-specific
        if (!$this->action->signature instanceof RsaSignature2017) {
            throw new RuntimeException('Signature is not RsaSignature2017');
        }
        // Signature's creator and actor don't match
        if (mb_strpos($this->action->signature->creator, $this->action->actor) !== 0) {
            throw new RuntimeException("Signature actor and activity actor don't match: \n
                {$this->action->signature->creator} != {$this->action->actor}");
        }
        // Sender an author match
        if ($this->action->actor !== $this->action->object->attributedTo) {
            throw new RuntimeException('Actor and object attribution don\'t match');
        }

        // Actor should have been retrieved on middleware so if real, it should exists on the DB
        $this->activityActor = Actor::where('activityId', $this->action->actor)->first();
        if (!$this->activityActor) {
            throw new RuntimeException('Could not find the actor on the DB');
        }

        $array = $this->action->toArray();
        unset($array['signature']['id'], $array['signature']['type'], $array['signature']['signatureValue']);
        // TODO: Base64-decode the signatureValue and verify it against the public key in signature[creator].
    }

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
