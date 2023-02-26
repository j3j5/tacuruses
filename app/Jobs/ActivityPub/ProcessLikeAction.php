<?php

namespace App\Jobs\ActivityPub;

use App\Domain\ActivityPub\Like as LikeAction;
use App\Models\ActivityPub\ActivityLike;
use App\Models\ActivityPub\Like;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessLikeAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private readonly LikeAction $action,
        private readonly ActivityLike $activity
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // First or create the actor
        $actor = $this->activity->actor;
        /** @var \App\Models\ActivityPub\Note $target */
        $target = $this->activity->target;

        // Store the like
        $like = Like::updateOrCreate(
            ['actor_id' => $actor->id, 'target_id' => $target->id],
            ['activityId' => $this->action->id]
        );

        // Send the accept back
        SendLikeAcceptToActor::dispatch($actor, $target, $this->activity);
    }

    /**
     *
     * @param \App\Domain\ActivityPub\Like $action
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @return void
     */
    protected function findLocalActor(LikeAction $action)
    {
        //
    }
}
