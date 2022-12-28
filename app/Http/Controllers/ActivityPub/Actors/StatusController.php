<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Contracts\APCompatible;
use App\Http\Controllers\Controller;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    protected string $username;
    protected APCompatible $status;

    /**
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\ActivityPub\LocalActor $user
     * @param string $status
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request, LocalActor $user, string $status)
    {
        $this->status = $user->model::findOrFail($status);

        if ($request->wantsJson()) {
            return $this->activityStatus($request);
        }
        return $this->status($request);
    }

    private function activityStatus(Request $request) : JsonResponse
    {
        $context = [
            '@context' => [
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
                    'blurhash' => 'toot:blurhash',
                    'focalPoint' => [
                        '@container' => '@list',
                        '@id' => 'toot:focalPoint',
                    ],
                ],
            ],
        ];

        $note = $this->status->getNote();
        return response()->activityJson(array_merge($context, $note->toArray()));
    }

    private function status(Request $request) : View
    {
        $data = ['status' => $this->status];
        return view('bots.status', $data);
    }
}
