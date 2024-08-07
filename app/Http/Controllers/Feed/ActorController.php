<?php

declare(strict_types=1);

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
        $items = $actor->notes()->latest()->take($feedSize)->get();
        $items = $items->load(['mediaAttachments'])->map(
            fn ($note) => $note->setRelation('actor', $actor)
        );
        $format = 'rss';
        return new Feed(
            $actor->name . ' feed',
            $items,
            route('actor.show', [$actor]),
            "feed::$format",
            $actor->bio ?? '',
            $actor->language,
            $actor->avatar,
            $format,
        );
    }
}
