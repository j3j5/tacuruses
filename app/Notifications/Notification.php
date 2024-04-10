<?php

namespace App\Notifications;

use App\Models\ActivityPub\Activity;
use App\Models\ActivityPub\Actor;
use Illuminate\Notifications\Notification as IlluminateNotification;

class Notification extends IlluminateNotification
{
    /**
     * The Actor who followed
     *
     * @var Actor
     */
    public readonly Actor $from;

    /**
     * The activity that "caused" this notification
     *
     * @var Activity
     */
    public readonly ?Activity $activity;

    /**
     * Create a new notification instance.
     */
    public function __construct(Actor $actor, ?Activity $activity = null)
    {
        $this->from = $actor;
        $this->activity = $activity;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }
}
