<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OnlyContentType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $contentType): Response
    {
        if (mb_strpos(haystack: (string) $request->header('Content-Type'), needle: $contentType) === false) {
            // if ($request->header('Content-Type') !== $contentType) {
            Log::debug("Wrong content-type, expected $contentType but found '" . $request->header('Content-Type') . "'", compact('request'));
            abort_if(app()->environment(['production', 'testing']), Response::HTTP_NOT_FOUND);
        }
        return $next($request);
    }
}
