<?php

namespace App\Jobs\ActivityPub;

use App\Domain\ActivityPub\Follow as FollowAction;
use App\Models\ActivityPub\Follow;
use App\Models\ActivityPub\LocalActor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

use function Safe\preg_match;

class ProcessFollowAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private readonly FollowAction $action)
    {
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
        $actor = FindActorInfo::dispatchSync($this->action->actor);
        // Store the follow
        $pattern = '#https://(?:[\w\.-]+)/(?<username>.*)$#';
        if (!preg_match($pattern, $this->action->target, $matches)) {
            throw new RuntimeException('No user found for target ID ' . $this->action->target);
        }
        $target = LocalActor::where('username', $matches['username'])->firstOrFail();
        $follow = Follow::updateOrCreate(
            ['actor_id' => $actor->id, 'target_id' => $target->id],
            ['remote_id' => $this->action->id]
        );

        SendAcceptToActor::dispatchAfterResponse($actor, $target, $follow);
    }
}
