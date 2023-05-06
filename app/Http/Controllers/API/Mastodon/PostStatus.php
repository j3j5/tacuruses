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
        if (!$request->boolean('draft')) {
            $note->publish();
        }

        if ($request->date('scheduled_at')) {
            // Schedule the job on the queue
            dispatch(function () use ($note) {
                $note->publish();
            })->delay(now()->diff($request->date('schedule_at')));
        }

        return new StatusResource($note);
    }
}
