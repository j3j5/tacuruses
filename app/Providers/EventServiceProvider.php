<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\DebugSubscriber;
use App\Listeners\NotificationsSubscriber;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        //
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
}
