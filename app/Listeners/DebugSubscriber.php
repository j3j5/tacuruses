<?php

namespace App\Listeners;

use App\Events\RemoteActorCreated;
use App\Events\RemoteActorUpdated;
use Illuminate\Support\Facades\Log;

class DebugSubscriber
{
    /**
     * Handle user logout events.
     */
    public function debug(RemoteActorCreated|RemoteActorUpdated $event) : void
    {
        if (config('app.debug')) {
            Log::debug(get_class($event));
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events) : array
    {
        return [
            RemoteActorCreated::class => 'debug',
            RemoteActorUpdated::class => 'debug',
        ];
    }
}
