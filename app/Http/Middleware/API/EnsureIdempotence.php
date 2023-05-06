<?php

namespace App\Http\Middleware\API;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class EnsureIdempotence
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->hasHeader('Idempotency-Key')) {
            return $next($request);
        }

        $key = (string) $request->header('Idempotency-Key', Str::random());

        if (Cache::has($key)) {
            return JsonResponse::fromJsonString(Cache::get($key));
        }

        $response = $next($request);

        Cache::set($key, $response->getContent());

        return $response;
    }
}
