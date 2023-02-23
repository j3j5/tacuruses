<?php

namespace App\Jobs\ActivityPub;

use App\Domain\ActivityPub\Like as LikeAction;
use App\Models\ActivityPub\Like;
use App\Models\ActivityPub\LocalActor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use function Safe\parse_url;

class ProcessLikeAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private readonly LikeAction $action)
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

        $path = ltrim(parse_url($this->action->target, PHP_URL_PATH), '/');
        [$actorId, $statusId] = explode('/', $path);

        /** @var \App\Models\ActivityPub\LocalActor $localActor */
        $localActor = LocalActor::where('slug', $actorId)->firstOrFail();

        /** @var \App\Domain\ActivityPub\Contracts\Note $target */
        $target = $localActor->getNote($statusId);

        // Store the like
        $like = Like::updateOrCreate(
            ['actor_id' => $actor->id, 'target_id' => $target->id],
            ['activityId' => $this->action->id]
        );

        // Send the accept back
        // SendLikeAcceptToActor::dispatchAfterResponse($actor, $target, $like);
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
