<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Can't use wantsJson() because it initializes $this->acceptableContentTypes
        // on the SymfonyRequest, which then doesn't get modified again
        // if (!$request->wantsJson()) {
        $acceptable = explode(separator: ',', string: (string) $request->header('Accept'));
        if (!isset($acceptable[0]) || !Str::contains(strtolower($acceptable[0]), ['/json', '+json'])) {
            $request->headers->set('Accept', 'application/json,' . $request->header('Accept'));
        }
        return $next($request);
    }
}
