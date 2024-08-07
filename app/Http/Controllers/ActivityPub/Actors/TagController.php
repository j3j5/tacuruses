<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Services\ActivityPub\Context;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    public function __invoke(Request $request, string $tag) : JsonResponse|View
    {
        if ($request->wantsJson()) {
            return response()->activityJson([
                '@context' => Context::ACTIVITY_STREAMS,
                'id' => $request->url(),
                'type' => 'OrderedCollection',
            ]);
        }

        return view('actors.tag', compact('tag'));
    }
}
