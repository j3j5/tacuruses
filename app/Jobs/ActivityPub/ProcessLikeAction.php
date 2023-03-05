<?php

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type\Extended\Activity\Like as ExtendedActivityLike;
use App\Models\ActivityPub\ActivityLike;
use App\Models\ActivityPub\Like;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class ProcessLikeAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private readonly ExtendedActivityLike $action,
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
        $target = $this->activity->target;
        if (!$target instanceof LocalNote) {
            throw new RuntimeException('The ActivityLike does not seem to have a valid target');
        }

        // Store the like
        $like = Like::updateOrCreate(
            ['actor_id' => $actor->id, 'target_id' => $target->id],
            ['activityId' => $this->action->id]
        );

        if ($actor instanceof RemoteActor) {
            // Send the accept back
            SendLikeAcceptToActor::dispatch($actor, $target, $this->activity);
        }
    }
}
