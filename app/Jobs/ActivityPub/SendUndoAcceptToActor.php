<?php

namespace App\Jobs\ActivityPub;

use App\Models\ActivityPub\ActivityUndo;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
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
    private readonly LocalActor|LocalNote $target;
    private readonly LocalActor $targetActor;
    private readonly ActivityUndo $undo;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Actor $actor, LocalActor|LocalNote $target, ActivityUndo $undo)
    {
        $this->actor = $actor;
        $this->target = $target;
        $this->undo = $undo;
        $this->targetActor = $target instanceof LocalNote ?
            $target->actor :
            $target;
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
        $this->sendSignedRequest(
            signer: $signer,
            request: $accept,
            inbox: $this->actor->inbox,
            actorSigning: $this->targetActor
        );

        $this->undo->markAsAccepted();
    }
}
