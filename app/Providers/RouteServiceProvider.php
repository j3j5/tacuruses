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
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('federation')
                ->group(base_path('routes/federation.php'));
        });

        Route::model('user', LocalActor::class);

        Route::bind('status', function(string $value, RoutingRoute $route) : LocalNote {
            if (!$route->hasParameter('user') || !$route->parameter('user') instanceof LocalActor) {
                throw new RuntimeException('Unresolvable param on route for status');
            }
            $note = LocalNote::withCount(['shares', 'likes'])
                ->where('id', $value)
                ->where('actor_id', $route->parameter('user')->id)
                ->firstOrFail();
            $note->setRelation('actor', $route->parameter('user'));
            return $note;
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        // RateLimiter::for('api', function (Request $request) {
        // return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        // });
    }
}
