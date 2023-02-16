<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Http\Resources\FollowCollection;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use Illuminate\Http\Request;

class FollowingController extends Controller
{
    /**
     *
     * @param \Illuminate\Http\Request $request
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return never
     */
    public function __invoke(Request $request, LocalActor $actor)
    {
        info(__CLASS__, ['request' => $request]);
        $perPage = 20;
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator */
        $following = $actor->following()->paginate($perPage);

        if ($request->missing(['page']) && $following->total() > $perPage) {
            return response()->activityJson([
                "@context" => Context::ACTIVITY_STREAMS,
                "id" => $actor->getFollowingUrl(),
                "type" => "OrderedCollection",
                "totalItems" => $following->total(),
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
