<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Models\ActivityPub\LocalActor;
use Illuminate\Http\Request;
use Spatie\Feed\Feed;

class ActorController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, LocalActor $actor) : Feed
    {
        $feedSize = 10;
        $items = $actor->notes()->latest()->take($feedSize)->get()
            ->map(
                fn ($note) => $note->setRelation('actor', $actor)
            );
        $format = 'atom';
        if ($request->routeIs('actor.feed.rss')) {
            $format = 'rss';
        }
        return new Feed(
            $actor->name . ' feed',
            $items,
            $request->url(),
            "feed::$format",
            $actor->bio ?? '',
            $actor->language,
            $actor->avatar_url,
            $format,
        );
    }
}
