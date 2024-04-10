<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ActivityPub\ActivityAnnounce;
use Illuminate\Broadcasting\PrivateChannel;

final class LocalNoteShared extends BaseEvent
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public readonly ActivityAnnounce $share)
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
