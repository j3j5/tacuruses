<?php

namespace App\Jobs\ActivityPub;

use App\Domain\ActivityPub\Undo;
use App\Models\ActivityPub\ActivityUndo;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ProcessUndoAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private readonly Undo $action,
        private readonly ActivityUndo $activity
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
        switch($this->activity->object_type) {
            case 'Follow':
                if (!$target instanceof LocalActor) {
                    throw new RuntimeException('The ActivityUndo does not seem to have a valid actor target');
                }
                $this->processUndoFollow();
                break;
            case 'Like':
                if (!$target instanceof LocalNote) {
                    throw new RuntimeException('The ActivityUndo do not seem to have a valid note target');
                }
                $this->processUndoLike();
                break;
            default:
                Log::notice('unknown action', [$this->action, $this->activity]);
                throw new RuntimeException('Unknown action');
        }

        if ($actor instanceof RemoteActor) {
            // Send the accept back
            SendUndoAcceptToActor::dispatch(
                $actor,
                $target,
                $this->activity->withoutRelations()
            );
        }
    }

    private function processUndoFollow() : void
    {
        $actor = $this->activity->actor;
        /** @var \App\Models\ActivityPub\LocalActor $target */
        $target = $this->activity->target;

        // Delete the follow relationship
        $actor->following()
            ->where('target_id', $target->id)
            ->where('activityId', $this->action->objectToUndo['id'])
            ->delete();
    }

    private function processUndoLike() : void
    {
        $actor = $this->activity->actor;
        /** @var \App\Models\ActivityPub\LocalNote $target */
        $target = $this->activity->target;

        $target->likes()
            ->where('actor_id', $actor->id)
            ->where('activityId', $this->action->objectToUndo['id'])
            ->delete();
    }
}
