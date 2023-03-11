<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Services\ActivityPub\Context;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteActivityController extends Controller
{
    /**
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\ActivityPub\LocalActor $actor
     * @param \App\Models\ActivityPub\LocalNote $note
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __invoke(Request $request, LocalActor $actor, LocalNote $note) : JsonResponse
    {
        $context = ['@context' => [
            Context::ACTIVITY_STREAMS,
            [
                'ostatus' => 'http://ostatus.org#',
                'atomUri' => 'ostatus:atomUri',
                'inReplyToAtomUri' => 'ostatus:inReplyToAtomUri',
                'conversation' => 'ostatus:conversation',
                'sensitive' => 'as:sensitive',
                'toot' => 'http://joinmastodon.org/ns#',
                'votersCount' => 'toot:votersCount',
                'Hashtag' => 'as:Hashtag',
            ],
        ]];

        $activity = $note->getAPCreate();

        return response()->activityJson(array_merge($context, $activity->toArray()));
    }
}
