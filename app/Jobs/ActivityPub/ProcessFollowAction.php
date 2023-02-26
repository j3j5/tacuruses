<?php

namespace App\Jobs\ActivityPub;

use App\Domain\ActivityPub\Follow as FollowAction;
use App\Models\ActivityPub\ActivityFollow;
use App\Models\ActivityPub\Follow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessFollowAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private readonly FollowAction $action,
        private readonly ActivityFollow $activity
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
        $actor = $this->activity->actor;
        $target = $this->activity->target;

        // Store the follow
        $follow = Follow::updateOrCreate(
            ['actor_id' => $actor->id, 'target_id' => $target->id],
            ['activityId' => $this->action->id]
        );

        SendFollowAcceptToActor::dispatch($actor, $target, $this->activity);
    }
}
