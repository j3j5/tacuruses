<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\Note;

final class LocalActorMentioned extends BaseEvent
{
    /**
     * The local actor being mentioned
     *
     * @var LocalActor
     */
    public readonly LocalActor $actor;

    /**
     * The note that mentions an actor on our server
     *
     * @var Note
     */
    public readonly Note $note;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(LocalActor $actor, Note $note)
    {
        $this->actor = $actor;
        $this->note = $note;
    }
}
