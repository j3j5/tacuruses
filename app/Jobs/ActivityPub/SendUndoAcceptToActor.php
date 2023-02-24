<?php

namespace App\Jobs\ActivityPub;

use App\Domain\ActivityPub\Contracts\Signer;
use App\Models\ActivityPub\Activity;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\Actor;
use App\Traits\SendsSignedRequests;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendUndoAcceptToActor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use SendsSignedRequests;

    private readonly Actor $actor;
    private readonly LocalActor $target;
    private readonly Activity $undo;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Actor $actor, LocalActor $target, Activity $undo)
    {
        $this->actor = $actor;
        $this->target = $target;
        $this->undo = $undo;
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
            'id' => $this->target->activityId . '#accepts/undo/' . $this->undo->slug,
            'type' => 'Accept',
            'actor' => $this->target->activityId,
            'object' => [
                'id' => $this->undo->activityId,
                'actor' => $this->actor->activityId,
                'type' => 'Undo',
                'object' => $this->target->activityId,
            ],
        ];
        $this->sendSignedRequest($signer, $accept);
    }
}
