<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type;
use App\Models\ActivityPub\ActivityQuoteRequest;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\RemoteActor;
use App\Services\ActivityPub\Context as ActivityPubContext;
use App\Services\ActivityPub\Signer;
use App\Traits\SendsSignedRequests;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Context;

use function Safe\parse_url;

final class SendQuoteRequestAcceptToActor extends BaseFederationJob implements ShouldQueue
{
    use SendsSignedRequests;

    private readonly LocalActor $targetActor;
    private readonly string $instance;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private readonly RemoteActor $actor,
        private readonly LocalNote $target,
        private readonly ActivityQuoteRequest $quoteRequest
    ) {
        $this->targetActor = $this->target->actor;

        $this->instance = (string) (parse_url($this->actor->inbox, PHP_URL_HOST) ?? $this->actor->inbox);  // @phpstan-ignore cast.string
        Context::add('toInstance', $this->instance);
        Context::add('actorSigning', $this->targetActor->id);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Signer $signer): void
    {
        $accept = Type::create('Accept', [
            '@context' => [
                ActivityPubContext::ACTIVITY_STREAMS,
                ActivityPubContext::$quoteRequest,
            ],
            'to' => $this->actor->activityId,
            'id' => $this->target->activityId . '#accepts/quouteReq/' . $this->quoteRequest->slug,
            'actor' => $this->target->actor->activity_id,
            'object' => [
                'id' => $this->quoteRequest->activityId,
                'actor' => $this->actor->activityId,
                'type' => 'QuoteRequest',
                'object' => $this->target->activityId,
                'instrument' => $this->quoteRequest->object['id'],
            ],
            'result' => route('actor.approved-quotes', [$this->target->actor, $this->quoteRequest]),
        ])->toArray();

        $this->sendSignedPostRequest(
            signer: $signer,
            actorSigning: $this->targetActor,
            url: $this->actor->inbox,
            data: $accept,
        );

        $this->quoteRequest->markAsAccepted();
    }

    /*
{
  "@context": [
    "https://www.w3.org/ns/activitystreams",
    {
      "QuoteRequest": "https://w3id.org/fep/044f#QuoteRequest"
    }
  ],
  "type": "Accept",
  "to": "https://example.com/users/bob",
  "id": "https://example.com/users/alice/activities/1234",
  "actor": "https://example.com/users/alice",
  "object": {
    "type": "QuoteRequest",
    "id": "https://example.com/users/bob/statuses/1/quote",
    "actor": "https://example.com/users/bob",
    "object": "https://example.com/users/alice/statuses/1",
    "instrument": "https://example.org/users/bob/statuses/1"
  },
  "result": "https://example.com/users/alice/stamps/1"
}

    */

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'instance:' . $this->instance,
            'signing:' . $this->targetActor->id,
            'accept',
            'federation-out',
        ];
    }
}
