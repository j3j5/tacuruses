<?php

declare(strict_types=1);

namespace App\Providers;

use GuzzleHttp\Utils;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class HttpServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Force a single User Agent for all requests going out
        Http::globalRequestMiddleware(fn ($request) => $request->withHeader(
            'User-Agent',
            config('federation.user-agent', Utils::defaultUserAgent())
        ));
    }
}
