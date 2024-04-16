<?php

declare(strict_types = 1);

namespace App\Listeners;

use App\Events\BaseEvent;
use App\Events\LocalActorFollowed;
use App\Events\LocalActorMentioned;
use App\Events\LocalNoteLiked;
use App\Events\LocalNotePublished;
use App\Events\LocalNoteReplied;
use App\Events\LocalNoteShared;
use App\Events\LocalNoteUpdated;
use App\Events\RemoteActorCreated;
use App\Events\RemoteActorUpdated;
use Illuminate\Support\Facades\Log;

class DebugSubscriber
{
    /**
     * Handle user logout events.
     */
    public function debug(BaseEvent $event) : void
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
            LocalActorFollowed::class => 'debug',
            LocalActorMentioned::class => 'debug',
            LocalNoteLiked::class => 'debug',
            LocalNotePublished::class => 'debug',
            LocalNoteReplied::class => 'debug',
            LocalNoteShared::class => 'debug',
            LocalNoteUpdated::class => 'debug',
            RemoteActorCreated::class => 'debug',
            RemoteActorUpdated::class => 'debug',
        ];
    }
}
