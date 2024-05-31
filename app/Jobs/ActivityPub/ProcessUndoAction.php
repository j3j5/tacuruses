<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type\Extended\Activity\Undo;
use App\Enums\ActivityTypes;
use App\Exceptions\AppException;
use App\Models\ActivityPub\ActivityUndo;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Webmozart\Assert\Assert;

final class ProcessUndoAction implements ShouldQueue
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
        Context::add('actor', $this->activity->actor_id);
        Context::add('target', $this->activity->target_id);
        Context::add('type', $this->activity->type);
        Context::add('object_type', $this->activity->object_type);
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
        switch(ActivityTypes::tryFrom(data_get($this->activity, 'object_type', ''))) {
            case ActivityTypes::FOLLOW:
                Assert::isInstanceOf($target, LocalActor::class);
                $this->processUndoFollow();
                break;
            case ActivityTypes::LIKE:
                Assert::isInstanceOf($target, LocalNote::class);
                $this->processUndoLike();
                break;
            default:
                Log::notice('unknown action', [$this->action, $this->activity]);
                throw new AppException('Unknown action');
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
        /** @var \App\Models\ActivityPub\Actor $actor */
        $actor = $this->activity->actor;
        /** @var \App\Models\ActivityPub\LocalActor $target */
        $target = $this->activity->target;

        // Delete the follow relationship
        $target->receivedFollows()
            ->where('actor_id', $actor->id)
            ->where('activityId', $this->action->object->id)    /* @phpstan-ignore-line */
            ->delete();
    }

    private function processUndoLike() : void
    {
        $actor = $this->activity->actor;
        /** @var \App\Models\ActivityPub\LocalNote $target */
        $target = $this->activity->target;

        $target->likes()
            ->where('actor_id', $actor->id)
            ->where('activityId', $this->action->object->id)    /* @phpstan-ignore-line */
            ->delete();
    }
}
