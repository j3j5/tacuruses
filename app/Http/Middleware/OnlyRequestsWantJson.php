<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

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
            abort_if(app()->environment(['production', 'testing']), 404);
        }

        return $next($request);
    }
}
