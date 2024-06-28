<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        using: function () {
            Route::prefix('/api/')->group(function () {
                Route::middleware('oembed')
                    ->group(base_path('routes/oembed.php'));

                Route::middleware('mastodon-api')
                    ->group(base_path('routes/mastodon-api.php'));
            });

            Route::middleware('federation')
                ->group(base_path('routes/federation.php'));

            // Route::middleware('web')
            // ->group(base_path('routes/auth.php'));
            Route::middleware('feeds')
                ->group(base_path('routes/feeds.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectTo(
            guests: '/',
            users: '/dashboard'
        );

        $middleware->alias([
            'debug' => \App\Http\Middleware\Debug::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'legacy' => \App\Http\Middleware\LegacyCheck::class,
            'no.cookies' => \App\Http\Middleware\NoCookies::class,
            'valid.http.signature' => \App\Http\Middleware\ActivityPub\VerifyHttpSignature::class,
        ]);

        $middleware->priority([
            \App\Http\Middleware\NoCookies::class,
            \App\Http\Middleware\ForceJsonResponse::class,

            // Cookies
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
        ]);

        $middleware->appendToGroup('web', [
            Illuminate\Cookie\Middleware\EncryptCookies::class,
            Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            Illuminate\Session\Middleware\StartSession::class,
            Illuminate\View\Middleware\ShareErrorsFromSession::class,
            Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->appendToGroup('api', [
            Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            // Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->appendToGroup('mastodon-api', [
            App\Http\Middleware\NoCookies::class,
            // \Illuminate\Routing\Middleware\ThrottleRequests::class . ':mastodon-api',
            App\Http\Middleware\ForceJsonResponse::class,
            Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->appendToGroup('federation', [
            // \Illuminate\Routing\Middleware\ThrottleRequests::class . ':federation',
            Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->appendToGroup('oembed', [
            App\Http\Middleware\NoCookies::class,
            // \Illuminate\Routing\Middleware\ThrottleRequests::class . ':oembed',
            App\Http\Middleware\ForceJsonResponse::class,
            Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->appendToGroup('feeds', [
            App\Http\Middleware\NoCookies::class,
            Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })->create();
