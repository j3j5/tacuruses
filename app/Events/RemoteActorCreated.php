<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ActivityPub\RemoteActor;

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
}
