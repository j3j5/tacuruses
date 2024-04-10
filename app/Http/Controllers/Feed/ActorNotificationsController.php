<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Feed\Feed;

class ActorNotificationsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function __invoke(Request $request)
    {
        $actor = $request->user();

        $feedSize = 10;
        $items = $actor->notes()->latest()->take($feedSize)->get();

        $format = 'rss';
        return new Feed(
            $actor->name . ' feed',
            $items,
            route('actor.show', [$actor]),
            "feed::$format",
            $actor->bio ?? '',
            $actor->language,
            $actor->avatar_url,
            $format,
        );
    }
}
