<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type;
use App\Models\ActivityPub\ActivityAnnounce;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\RemoteActor;
use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
use App\Traits\SendsSignedRequests;
use Illuminate\Contracts\Queue\ShouldQueue;

use function Safe\parse_url;

final class SendAnnounceAcceptToActor extends BaseFederationJob implements ShouldQueue
{
    use SendsSignedRequests;

    private readonly RemoteActor $actor;
    private readonly LocalNote $target;
    private readonly LocalActor $targetActor;
    private readonly ActivityAnnounce $announce;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RemoteActor $actor, LocalNote $target, ActivityAnnounce $announce)
    {
        $this->actor = $actor;
        $this->target = $target;
        $this->targetActor = $target->actor;
        $this->announce = $announce;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Signer $signer): void
    {
        $accept = Type::create('Accept', [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->target->activityId . '#accepts/announce/' . $this->announce->slug,
            'actor' => $this->target->activityId,
            'object' => [
                'id' => $this->announce->activityId,
                'actor' => $this->actor->activityId,
                'type' => 'Announce',
                'object' => $this->target->activityId,
            ],
        ])->toArray();

        $this->sendSignedPostRequest(
            signer: $signer,
            actorSigning: $this->targetActor,
            url: $this->actor->inbox,
            data: $accept,
        );

        $this->announce->markAsAccepted();
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        /** @var string $instance */
        $instance = (string) (parse_url($this->actor->inbox, PHP_URL_HOST) ?? $this->actor->inbox);  // @phpstan-ignore cast.string
        return [
            'federation-out',
            'accept',
            'instance:' . $instance,
            'signing:' . $this->targetActor->id
        ];
    }
}
