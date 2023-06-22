<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Http\Middleware\OnlyRequestsWantJson;
use App\Http\Resources\ActivityPub\FollowCollection;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowingController extends Controller
{
    public function __construct()
    {
        $this->middleware(OnlyRequestsWantJson::class);
    }

    /**
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function __invoke(Request $request, LocalActor $actor) : JsonResponse|FollowCollection
    {
        $perPage = 20;
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator */
        $following = $actor->following()->paginate($perPage);

        if ($request->missing(['page']) && $following->total() > $perPage) {
            return response()->activityJson([
                '@context' => Context::ACTIVITY_STREAMS,
                'id' => $actor->following_url,
                'type' => 'OrderedCollection',
                'totalItems' => $following->total(),
                'first' => $following->url(0),
                // First items, order by desc (the last item on this collection is the first ever published)
                'last' => $following->url($following->lastPage()),
            ]);
        }
        $collection = new FollowCollection($following);
        $collection->user = $actor;
        return $collection;
    }
}
