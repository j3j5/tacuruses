<?php

namespace App\Http\Controllers\API\Mastodon;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Mastodon\PostStatusRequest;
use App\Http\Resources\API\Mastodon\StatusResource;
use App\Jobs\Application\CreateNewNote;

class PostStatus extends Controller
{
    public function __invoke(PostStatusRequest $request) : StatusResource
    {
        // Post Status
        /** @var \App\Models\ActivityPub\LocalNote $note */
        $note = CreateNewNote::dispatchSync($request->validated());
        if (!$request->boolean('draft') && (!$request->filled('scheduled_at')) || $request->date('scheduled_at')->isFuture()) {
            $note->publish();
        }

        return new StatusResource($note);
    }
}
