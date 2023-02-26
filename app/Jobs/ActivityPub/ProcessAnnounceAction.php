<?php

namespace App\Jobs\ActivityPub;

use App\Domain\ActivityPub\Announce;
use App\Models\ActivityPub\ActivityAnnounce;
use App\Models\ActivityPub\Share;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAnnounceAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private readonly Announce $action,
        private readonly ActivityAnnounce $activity
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
        info(__FILE__ . '.' . __LINE__);
        $actor = $this->activity->actor;
        /** @var \App\Models\ActivityPub\Note $target */
        $target = $this->activity->target;
        // Store the like
        $share = Share::updateOrCreate(
            ['actor_id' => $actor->id, 'target_id' => $target->id],
            ['activityId' => $this->action->id]
        );

        // Send the accept back
        SendAnnounceAcceptToActor::dispatch($actor, $target, $this->activity);
    }
}
