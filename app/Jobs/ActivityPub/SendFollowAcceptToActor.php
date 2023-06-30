<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use App\Models\ActivityPub\ActivityFollow;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\RemoteActor;
use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
use App\Traits\SendsSignedRequests;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendFollowAcceptToActor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use SendsSignedRequests;

    private readonly RemoteActor $actor;
    private readonly LocalActor $target;
    private readonly LocalActor $targetActor;
    private readonly ActivityFollow $follow;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RemoteActor $actor, LocalActor $target, ActivityFollow $follow)
    {
        $this->actor = $actor;
        $this->target = $target;
        $this->targetActor = $target;
        $this->follow = $follow;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Signer $signer)
    {
        $accept = [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->target->activityId . '#accepts/follows/' . $this->follow->slug,
            'type' => 'Accept',
            'actor' => $this->target->activityId,
            'object' => [
                'id' => $this->follow->activityId,
                'actor' => $this->actor->activityId,
                'type' => 'Follow',
                'object' => $this->target->activityId,
            ],
        ];

        $this->sendSignedPostRequest(
            signer: $signer,
            data: $accept,
            inbox: $this->actor->inbox,
            actorSigning: $this->targetActor
        );

        $this->follow->markAsAccepted();
    }
}
