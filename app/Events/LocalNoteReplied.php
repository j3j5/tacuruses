<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ActivityPub\Note;
use Illuminate\Broadcasting\PrivateChannel;

final class LocalNoteReplied extends BaseEvent
{
    /**
     * Represents the new note that replies to a local note
     *
     * @var Note
     */
    public readonly Note $note;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Note $note)
    {
        $this->note = $note;
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
