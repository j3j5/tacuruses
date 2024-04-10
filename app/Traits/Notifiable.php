<?php

namespace App\Traits;

use App\Models\Notification;
use Illuminate\Notifications\Notifiable as IlluminateNotifiable;

trait Notifiable
{
    use IlluminateNotifiable;

    /**
     * Get the entity's notifications. Overwrite Illuminates method
     * to use our own class.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable')->latest();
    }
}