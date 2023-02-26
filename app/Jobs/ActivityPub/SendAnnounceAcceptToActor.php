<?php

namespace App\Jobs\ActivityPub;

use App\Domain\ActivityPub\Contracts\Signer;
use App\Domain\ActivityPub\Like as ActivityPubLike;
use App\Models\ActivityPub\ActivityAnnounce;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\Note;
use App\Models\ActivityPub\RemoteActor;
use App\Services\ActivityPub\Context;
use App\Traits\SendsSignedRequests;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAnnounceAcceptToActor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use SendsSignedRequests;

    private readonly RemoteActor $actor;
    private readonly Note $target;
    private readonly LocalActor $targetActor;
    private readonly ActivityAnnounce $announce;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RemoteActor $actor, Note $target, ActivityAnnounce $announce)
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
    public function handle(Signer $signer)
    {
        $accept = [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->target->activityId . '#accepts/announce/' . $this->announce->slug,
            'type' => 'Accept',
            'actor' => $this->target->activityId,
            'object' => [
                'id' => $this->announce->activityId,
                'actor' => $this->actor->activityId,
                'type' => ActivityPubLike::TYPE,
                'object' => $this->target->activityId,
            ],
        ];
        $this->sendSignedRequest($signer, $accept);

        $this->announce->markAsAccepted();
    }
}
