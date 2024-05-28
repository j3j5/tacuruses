<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use App\Models\ActivityPub\ActivityCreate;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
use App\Traits\SendsSignedRequests;
use Illuminate\Contracts\Queue\ShouldQueue;

use function Safe\parse_url;

final class SendCreateAcceptToActor extends BaseFederationJob implements ShouldQueue
{
    use SendsSignedRequests;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private readonly LocalActor $actor, private ActivityCreate $create)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Signer $signer): void
    {
        $accept = [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->create->target->activityId . '#accepts/create/' . $this->create->slug,
            'type' => 'Accept',
            'actor' => $this->create->target->actor->activityId,
            'object' => [
                'id' => $this->create->activityId,
                'actor' => $this->create->target->actor->activityId,
                'type' => 'Create',
                'object' => $this->create->target->activityId,
            ],
        ];

        $this->sendSignedPostRequest(
            signer: $signer,
            actorSigning: $this->actor,
            data: $accept,
            url: $this->create->target->actor->inbox,
        );

        $this->create->markAsAccepted();
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        /** @var string $instance */
        $instance = (string) (parse_url($this->create->target->actor->inbox, PHP_URL_HOST) ?? $this->create->target->actor->inbox);  // @phpstan-ignore cast.string
        return [
            'federation-out',
            'accept',
            'instance:' . $instance,
            'signing:' . $this->actor->id
        ];
    }
}
