<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Debug
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {


        if (config('app.debug')) {
            Log::debug('request');
            Log::debug((string)$request);
        }
        $response = $next($request);
        if (config('app.debug')) {
            Log::debug('response');
            Log::debug((string)$response);
        }

        return $response;
    }
}
