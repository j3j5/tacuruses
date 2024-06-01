<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FallbackController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request) : void
    {
        Log::channel('fallback')->info($request->url(), [$request]);
        abort(418);
    }
}
