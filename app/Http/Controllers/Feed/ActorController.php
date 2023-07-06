<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Http\Request;
use Spatie\Feed\Feed;

class ActorController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, LocalActor $actor)
    {
        $items = $actor->notes()->latest()->take(10)->get()->map(fn (LocalNote $note) => $note->setRelation('actor', $actor));
        $view = 'feed::atom';
        $format = 'atom';
        if ($request->routeIs('actor.feed.rss')) {
            $view = 'feed::rss';
            $format = 'rss';
        }
        return new Feed(
            $actor->name . ' feed',
            $items,
            $request->url(),
            $view,
            $actor->bio ?? '',
            $actor->language,
            $actor->avatar_url,
            $format,
        );
    }
}
