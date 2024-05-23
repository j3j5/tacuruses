<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ActivityPub\ActivityFollow;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\LocalActor;

class LocalActorFollowed extends BaseEvent
{

    /**
     * Activity that created the relationship
     *
     * @var ActivityFollow
     */
    public readonly ActivityFollow $activity;

    /**
     * The local actor being followed
     *
     * @var LocalActor
     */
    public readonly LocalActor $actor;

    /**
     * The actor who started following a local actor
     *
     * @var Actor
     */
    public readonly Actor $follower;

    /**
     * Create a new event instance.
     */
    public function __construct(ActivityFollow $activity)
    {
        $this->activity = $activity;
        $this->actor = $activity->target;
        $this->follower = $activity->actor;
    }
}
