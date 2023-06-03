<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LegacyCheck
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
        if ($request->routeIs('legacy.note.show')) {
            $newUrl = route('note.show', $request->route()->parameters());
            return redirect($newUrl, 301);
        }
        return $next($request);
    }
}
