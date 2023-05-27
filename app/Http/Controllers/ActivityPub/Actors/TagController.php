<?php

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Services\ActivityPub\Context;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function __invoke(Request $request, string $tag)
    {
        if ($request->wantsJson()) {
            return response()->activityJson([
                '@context' => Context::ACTIVITY_STREAMS,
                'id' => $request->url(),
                'type' => 'OrderedCollection',
            ]);
        }

        return view('bots.tag', compact('tag'));
    }
}
