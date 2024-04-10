<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Channels\DatabaseChannel as IlluminateDatabaseChannel;
use Illuminate\Notifications\Notification;

class DatabaseChannel extends IlluminateDatabaseChannel
{

    /**
     * Build an array payload for the DatabaseNotification Model.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array
     */
    protected function buildPayload($notifiable, Notification $notification)
    {
        return [
            'id' => $notification->id,
            'type' => method_exists($notification, 'databaseType')
                        ? $notification->databaseType($notifiable)
                        : get_class($notification),
            'data' => $this->getData($notifiable, $notification),
            'read_at' => null,
            'from_actor_id' => data_get($notification, 'from.id'),
            'activity_id' => data_get($notification, 'activity.id'),
        ];
    }
}
