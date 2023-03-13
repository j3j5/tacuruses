<?php

namespace App\Events;

use App\Models\ActivityPub\LocalNote;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocalNotePublished
{
    use Dispatchable, SerializesModels;

    public readonly LocalNote $note;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(LocalNote $note)
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
