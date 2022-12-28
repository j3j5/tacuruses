<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use Illuminate\Http\Request;

class StatusActivityController extends Controller
{
    /**
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\ActivityPub\LocalActor $user
     * @param string $status
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, LocalActor $user, string $status)
    {
        $apModel = $user->model::findOrFail($status);

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

        $activity = $apModel->getCreateActivity();

        return response()->activityJson(array_merge($context, $activity->toArray()));
    }
}
