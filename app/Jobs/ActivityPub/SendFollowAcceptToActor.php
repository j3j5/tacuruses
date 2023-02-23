<?php

namespace App\Jobs\ActivityPub;

use App\Domain\ActivityPub\Contracts\Signer;
use App\Models\ActivityPub\Follow;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\RemoteActor;
use App\Traits\SendsSignedRequests;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFollowAcceptToActor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use SendsSignedRequests;

    private readonly RemoteActor $actor;
    private readonly LocalActor $target;
    private readonly Follow $follow;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RemoteActor $actor, LocalActor $target, Follow $follow)
    {
        $this->actor = $actor;
        $this->target = $target;
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
            '@context' => 'https://www.w3.org/ns/activitystreams',
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
        $this->sendSignedRequest($signer, $accept);
    }
}
