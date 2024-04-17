<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type\Extended\Activity\Follow as ExtendedActivityFollow;
use App\Models\ActivityPub\ActivityFollow;
use App\Models\ActivityPub\Follow;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webmozart\Assert\Assert;

final class ProcessFollowAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private readonly ExtendedActivityFollow $action,
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

        Assert::isInstanceOf($target, LocalActor::class);

        // Store the follow
        $follow = Follow::updateOrCreate(
            ['actor_id' => $actor->id, 'target_id' => $target->id],
            ['activityId' => $this->action->get('id')]
        );

        if ($actor instanceof RemoteActor) {
            SendFollowAcceptToActor::dispatch($actor, $target, $this->activity);
        }
        $follow->accept();
    }
}
