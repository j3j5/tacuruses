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

class FollowingController extends Controller
{
    public const PER_PAGE = 20;

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
        /** @var \Illuminate\Pagination\LengthAwarePaginator */
        $following = $actor->following()->with('target')->paginate(self::PER_PAGE);

        if ($request->missing(['page']) && $following->total() > self::PER_PAGE) {
            // When quering the id (w/o page), if there is more than one page,
            // we only return the reference, but the actual items come
            // on OrderedCollectionPages
            return response()->activityJson(Type::create('OrderedCollection', [
                '@context' => Context::ACTIVITY_STREAMS,
                'id' => $actor->following_url,
                'totalItems' => $following->total(),
                'first' => $following->url(0),
                // First items, order by desc (the last item on this collection is the first ever published)
                'last' => $following->url($following->lastPage()),
            ])->toArray());
        }

        $collection = new FollowCollection($following);
        $collection->user = $actor;
        $collection->following = true;
        return $collection;
    }
}
