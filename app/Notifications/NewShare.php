<?php

namespace App\Notifications;

use App\Enums\NotificationTypes;
use App\Models\ActivityPub\Activity;
use App\Models\ActivityPub\ActivityAnnounce;
use App\Models\ActivityPub\Actor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

// use Illuminate\Notifications\Messages\MailMessage;

class NewShare extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The Actor who followed
     */
    public readonly Actor $from;

    /**
     * The activity that "caused" this notification
     *
     * @var ActivityAnnounce
     */
    public readonly ?Activity $activity;

    /**
     * Create a new notification instance.
     */
    public function __construct(Actor $actor, ?ActivityAnnounce $activity = null)
    {
        $this->from = $actor;
        $this->activity = $activity;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'key' => 'notifications.new-share',
            'replace' => [
                'user' => $this->from->name,
                'username' => $this->from->canonical_username,
                'instance' => $this->from->domain,
            ],
        ];
    }

    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return NotificationTypes::SHARE->value;
    }
}
