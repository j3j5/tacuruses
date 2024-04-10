<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use ActivityPhp\Type;
use App\Http\Controllers\Controller;
use App\Http\Middleware\OnlyRequestsWantJson;
use App\Http\Resources\ActivityPub\FollowCollection;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowersController extends Controller
{
    public const PER_PAGE = 20;

    public function __construct()
    {
        $this->middleware(OnlyRequestsWantJson::class);
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function __invoke(Request $request, LocalActor $actor) : JsonResponse|FollowCollection
    {
        /** @var \Illuminate\Pagination\LengthAwarePaginator */
        $followers = $actor->followers()->paginate(self::PER_PAGE);

        if ($request->missing(['page']) && $followers->total() > self::PER_PAGE) {
            // When quering the id (w/o page), if there is more than one page,
            // we only return the reference, but the actual items come
            // on OrderedCollectionPages
            return response()->activityJson(Type::create('OrderedCollection', [
                '@context' => Context::ACTIVITY_STREAMS,
                'id' => $actor->followers_url,
                'totalItems' => $followers->total(),
                'first' => $followers->url(0),
                // First items, order by desc (the last item on this collection is the first ever published)
                'last' => $followers->url($followers->lastPage()),
            ])->toArray());
        }

        $collection = new FollowCollection($followers);
        $collection->user = $actor;
        return $collection;
    }
}
