<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Scopes\Published;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use RuntimeException;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // RateLimiter::for('api', function (Request $request) {
        // return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        // });

        Route::model('actor', LocalActor::class);
        // Federation routes don't support accessing the Note w/o the actor
        Route::bind('note', function (string $value, RoutingRoute $route) : LocalNote {
            if (!$route->hasParameter('actor') || !$route->parameter('actor') instanceof LocalActor) {
                throw new RuntimeException('Unresolvable param on route for note');
            }
            /** @var \App\Models\ActivityPub\LocalNote $note */
            $note = LocalNote::withCount(['shares', 'likes'])
                ->where('id', $value)
                ->where('actor_id', $route->parameter('actor')->id)
                ->tap(new Published())
                ->firstOrFail();
            return $note->setRelation('actor', $route->parameter('actor'));
        });

        // Mastodon API does support accessing the note by ID only (no actor needed, it's on the login)
        Route::model('status', LocalNote::class);
    }
}
