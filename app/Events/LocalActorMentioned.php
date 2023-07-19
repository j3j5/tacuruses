<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\Note;
use Illuminate\Broadcasting\PrivateChannel;

final class LocalActorMentioned extends BaseEvent
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(private LocalActor $actor, private Note $note)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
