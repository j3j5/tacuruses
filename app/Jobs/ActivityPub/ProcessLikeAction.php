<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type\Extended\Activity\Like as ExtendedActivityLike;
use App\Models\ActivityPub\ActivityLike;
use App\Models\ActivityPub\Like;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;

final class ProcessLikeAction implements ShouldQueue
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
        Context::add('actor', $this->activity->actor_id);
        Context::add('target', $this->activity->target_id);
        Context::add('type', $this->activity->type);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // First or create the actor
        $actor = $this->activity->actor;
        $target = $this->activity->target;

        // Store the like
        $like = Like::updateOrCreate(
            ['actor_id' => $actor->id, 'target_id' => $target->id],
            ['activityId' => $this->action->get('id')]
        );

        if ($actor instanceof RemoteActor) {
            // Send the accept back
            SendLikeAcceptToActor::dispatch($actor, $target, $this->activity);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'instance-origin:' . $this->activity->actor->domain,
            'target-actor:' . $this->activity->target->actor_id,
            'target-note:' . $this->activity->target->id,
            'like',
            'federation-in',
        ];
    }
}
