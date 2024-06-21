<?php

declare(strict_types=1);

namespace App\Recorders;

use App\Enums\Pulse\RecordTypes;
use App\Events\LocalActorFollowed;
use App\Events\LocalActorMentioned;
use App\Events\LocalNoteLiked;
use App\Events\LocalNoteReplied;
use App\Events\LocalNoteShared;
use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Recorders\Concerns;

class Interactions
{
    use Concerns\Sampling;

    /**
     * The events to listen for.
     *
     * @var array<int, class-string>
     */
    public array $listen = [
        LocalNoteLiked::class,
        LocalNoteReplied::class,
        LocalNoteShared::class,
        LocalActorMentioned::class,
        LocalActorFollowed::class,
    ];

    /**
     * Record the deployment.
     */
    public function record(LocalNoteLiked|LocalNoteReplied|LocalNoteShared|LocalActorMentioned|LocalActorFollowed $event): void
    {
        if ($this->shouldSample() === false) {
            return;
        }

        /** @var int $targetActorId */
        $targetActorId = match(true) {
            $event instanceof LocalNoteLiked => $event->like->target->actor_id,
            $event instanceof LocalNoteReplied => $event->noteReplied->actor_id,
            $event instanceof LocalNoteShared => $event->share->target->actor_id,
            $event instanceof LocalActorMentioned => $event->actor->id,
            $event instanceof LocalActorFollowed => $event->actor->id,
        };

        // Record by actor
        Pulse::record(
            RecordTypes::ACTOR_INTERACTIONS->value,
            (string) $targetActorId
        )->count();

        // These events don't have a note associated
        if (in_array(get_class($event), [LocalActorMentioned::class,LocalActorFollowed::class])) {
            return;
        }

        /** @var int $targetNoteId */
        $targetNoteId = match(true) { // @phpstan-ignore match.unhandled
            $event instanceof LocalNoteLiked => $event->like->target->id,
            $event instanceof LocalNoteReplied => $event->noteReplied->id,
            $event instanceof LocalNoteShared => $event->share->target->id,
        };

        // Record by note
        Pulse::record(
            RecordTypes::NOTE_INTERACTIONS->value,
            (string) $targetNoteId
        )->count();
    }
}
