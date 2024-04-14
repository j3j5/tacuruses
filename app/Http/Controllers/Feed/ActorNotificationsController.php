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

    public function __invoke(Request $request, string $format) : Feed
    {
        /** @var \App\Models\ActivityPub\LocalActor $actor */
        $actor = $request->user();

        $feedSize = 10;
        /** @var \Illuminate\Database\Eloquent\Collection<\App\Models\Notification> $items */
        $items = $actor->notifications()->latest()->take($feedSize)->get();

        return new Feed(
            title: $actor->name . ' notifications feed',
            items: $items,
            url: route('feed.notifications', [$actor]),
            view: "feed::$format",
            description: '',
            language: $actor->language,
            image: $actor->avatar,
            format: $format,
        );
    }
}
