<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OnlyRequestsWantJson
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
        // Only accept proper requests on prod
        if (!$request->wantsJson()) {
            Log::debug('Request should want JSON but does not!', compact('request'));
            abort_if(app()->environment(['production', 'testing']), 404);
        }

        return $next($request);
    }
}
