<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type;
use App\Models\ActivityPub\ActivityUndo;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
use App\Traits\SendsSignedRequests;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendUndoAcceptToActor extends BaseFederationJob implements ShouldQueue
{
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
    public function handle(Signer $signer): void
    {
        $accept = Type::create('Accept', [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->target->activityId . '#accepts/undo/' . $this->undo->slug,
            'actor' => $this->target->activityId,
            'object' => [
                'id' => $this->undo->activityId,
                'actor' => $this->actor->activityId,
                'type' => 'Undo',
                'object' => $this->target->activityId,
            ],
        ])->toArray();
        $this->sendSignedPostRequest(
            signer: $signer,
            actorSigning: $this->targetActor,
            data: $accept,
            url: $this->actor->inbox,
        );

        $this->undo->markAsAccepted();
    }
}
