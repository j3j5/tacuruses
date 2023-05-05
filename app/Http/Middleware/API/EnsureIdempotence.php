<?php

namespace App\Http\Middleware\API;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EnsureIdempotence
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
        if (!$request->hasHeader('Idempotency-Key')) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key');

        if (Cache::has($key)) {
            return JsonResponse::fromJsonString(Cache::get($key));
        }

        $response = $next($request);

        Cache::set($key, $response->getContent());

        return $response;
    }
}
