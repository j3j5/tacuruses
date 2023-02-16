<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Domain\ActivityPub\Contracts\Actor;
use App\Domain\ActivityPub\Contracts\Note;
use App\Http\Controllers\Controller;
use App\Services\ActivityPub\Context;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public Actor $actor;

    /**
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Domain\ActivityPub\Contracts\Actor $actor
     * @param string $status
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request, Actor $actor, string $status)
    {
        $this->actor = $actor;
        $note = $this->actor->getNote($status);
        if ($request->wantsJson()) {
            return $this->activityStatus($note);
        }
        return $this->status($note);
    }

    private function activityStatus(Note $note) : JsonResponse
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

        return response()->activityJson(
            array_merge($context, $note->getAPNote()->toArray())
        );
    }

    private function status(Note $note) : View
    {
        $data = ['note' => $note];
        return view('bots.status', $data);
    }
}
