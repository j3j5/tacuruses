<?php

namespace App\Providers;

use App\Events\LocalNotePublished;
use App\Listeners\DebugSubscriber;
use App\Listeners\NotificationsSubscriber;
use App\Listeners\SendNoteToFollowers;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        LocalNotePublished::class => [
            SendNoteToFollowers::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        DebugSubscriber::class,
        NotificationsSubscriber::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
