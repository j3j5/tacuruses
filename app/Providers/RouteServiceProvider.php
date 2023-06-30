<?php

namespace App\Providers;

use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
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
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // RateLimiter::for('api', function (Request $request) {
        // return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        // });

        $this->routes(function () {
            Route::middleware('federation')
                ->group(base_path('routes/federation.php'));

            Route::middleware('mastodon-api')
                ->group(base_path('routes/mastodon-api.php'));
        });

        Route::model('actor', LocalActor::class);

        Route::bind('note', function (string $value, RoutingRoute $route) : LocalNote {
            if (!$route->hasParameter('actor') || !$route->parameter('actor') instanceof LocalActor) {
                throw new RuntimeException('Unresolvable param on route for note');
            }
            /** @var \App\Models\ActivityPub\LocalNote $note */
            $note = LocalNote::withCount(['shares', 'likes'])
                ->where('id', $value)
                ->where('actor_id', $route->parameter('actor')->id)
                ->published()
                ->firstOrFail();
            return $note->setRelation('actor', $route->parameter('actor'));
        });
    }
}
