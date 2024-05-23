<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type\Extended\Activity\Announce;
use App\Models\ActivityPub\ActivityAnnounce;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\RemoteActor;
use App\Models\ActivityPub\Share;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

final class ProcessAnnounceAction implements ShouldQueue
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
    public function handle(): void
    {
        $actor = $this->activity->actor;
        $target = $this->activity->target;
        if (!$target instanceof LocalNote) {
            throw new RuntimeException('The ActivityAnnounce does not seem to have a valid target');
        }

        // Store the share
        Share::updateOrCreate(
            ['actor_id' => $actor->id, 'target_id' => $target->id],
            ['activityId' => $this->action->get('id')]
        );

        if ($actor instanceof RemoteActor) {
            // Send the accept back
            SendAnnounceAcceptToActor::dispatch($actor, $target, $this->activity);
        }
    }
}
