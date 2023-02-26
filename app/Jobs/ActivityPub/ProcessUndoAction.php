<?php

namespace App\Jobs\ActivityPub;

use App\Domain\ActivityPub\Undo;
use App\Models\ActivityPub\ActivityUndo;
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
        private ActivityUndo $activity
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
        info(__FILE__ . ':' . __LINE__, );
        info($this->activity->object_type);
        switch($this->activity->object_type) {
            case 'Follow':
                $this->processUndoFollow();
                break;
            case 'Like':
                $this->processUndoLike();
                break;
            default:
                Log::notice('unknown action', [$this->action, $this->activity]);
                throw new RuntimeException('Unknown action');
        }

        // Send the accept back
        SendUndoAcceptToActor::dispatch(
            $this->activity->actor,
            $this->activity->target,
            $this->activity->withoutRelations()
        );
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
        /** @var \App\Models\ActivityPub\Note $target */
        $target = $this->activity->target;
        $target->likes()
            ->where('actor_id', $actor->id)
            ->where('activityId', $this->action->objectToUndo['id'])
            ->delete();
    }
}
