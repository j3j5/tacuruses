<?php

namespace App\Events;

use App\Models\ActivityPub\ActivityUndo;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\LocalActor;

class LocalActorUnfollowed extends BaseEvent
{

    /**
     * Activity that created the relationship
     *
     * @var ActivityUndo
     */
    public readonly ActivityUndo $activity;

    /**
     * The local actor being unfollowed
     *
     * @var LocalActor
     */
    public readonly LocalActor $actor;

    /**
     * The actor who stopped following a local actor
     *
     * @var Actor
     */
    public readonly Actor $unfollower;

    /**
     * Create a new event instance.
     */
    public function __construct(ActivityUndo $activity)
    {
        $this->activity = $activity;
        $this->actor = $activity->target;
        $this->unfollower = $activity->actor;
    }
}
