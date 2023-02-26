<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Http\Resources\FollowCollection;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowersController extends Controller
{
    /**
     *
     * @param \Illuminate\Http\Request $request
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return \Illuminate\Http\JsonResponse|\App\Http\Resources\FollowCollection
     */
    public function __invoke(Request $request, LocalActor $actor) : JsonResponse|FollowCollection
    {
        $perPage = 20;
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator */
        $followers = $actor->followers()->paginate($perPage);

        if ($request->missing(['page']) && $followers->total() > $perPage) {
            return response()->activityJson([
                '@context' => Context::ACTIVITY_STREAMS,
                'id' => $actor->getFollowersUrl(),
                'type' => 'OrderedCollection',
                'totalItems' => $followers->total(),
                'first' => $followers->url(0),
                // First items, order by desc (the last item on this collection is the first ever published)
                'last' => $followers->url($followers->lastPage()),
            ]);
        }
        $collection = new FollowCollection($followers);
        $collection->user = $actor;
        return $collection;
    }
}
