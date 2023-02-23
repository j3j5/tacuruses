<?php

namespace App\Jobs\ActivityPub;

use App\Domain\ActivityPub\Undo;
use App\Models\ActivityPub\Action;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\LocalActor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ProcessUndoAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Actor $actor;
    private LocalActor $target;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private readonly Undo $action)
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
        info(__FILE__ . '.' . __LINE__);
        info('action', [$this->action]);
        // First or create the actor
        try {
            $this->target = LocalActor::where('activityId', $this->action->target)->firstOrFail();
        } catch (ModelNotFoundException) {
            Log::info('Target not found among local actors', ['action' => $this->action]);
            return;
        }
        /** @var \App\Models\ActivityPub\Actor $actor */
        $this->actor = FindActorInfo::dispatchSync($this->action->actor);

        // Find the action on DB
        try {
            $action = Action::where('activityId', $this->action->id)->firstOrFail();
        } catch (ModelNotFoundException) {
            Log::info('Original action to undo not found on the db, ignoring', ['action' => $this->action]);
            return;
        }
        $action->object_type = $this->action->objectToUndo['type'];
        $action->actor_id = $this->actor->id;
        $action->target_id = $this->target->id;
        $action->save();

        switch($action->object_type) {
            case 'Follow':
                $this->processUndoFollow();
                break;
            default:
                Log::notice('unknown action', [$this->action, $action]);
                throw new RuntimeException('Unknown action');
        }

        // Send the accept back
        SendUndoAcceptToActor::dispatchAfterResponse($this->actor, $this->target, $action);
    }

    private function processUndoFollow()
    {
        Log::debug($this->actor->following()->where('target_id', $this->target->id)->where('activityId', $this->action->objectToUndo['id'])->toSql());
        Log::debug($this->actor->following()->where('target_id', $this->target->id)->where('activityId', $this->action->objectToUndo['id'])->getBindings());

        $this->actor->following()->where('target_id', $this->target->id)->where('activityId', $this->action->objectToUndo['id'])->delete();
    }
}
