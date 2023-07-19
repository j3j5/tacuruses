<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ActivityPub\RemoteActor;
use Illuminate\Broadcasting\PrivateChannel;

final class RemoteActorCreated extends BaseEvent
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(private readonly RemoteActor $actor)
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
