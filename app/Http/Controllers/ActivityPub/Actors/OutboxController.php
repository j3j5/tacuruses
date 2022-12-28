<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Http\Resources\OutboxCollection;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use Illuminate\Http\Request;

class OutboxController extends Controller
{
    /**
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\ActivityPub\LocalActor $user
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \App\Http\Resources\OutboxCollection
     */
    public function __invoke(Request $request, LocalActor $user)
    {
        $perPage = 20;
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator */
        $statuses = $user->getStatuses();

        if ($request->missing(['page']) && $statuses->total() > $perPage) {
            return response()->activityJson([
                '@context' => Context::ACTIVITY_STREAMS,
                'id' => $user->outboxUrl(),
                'type' => 'OrderedCollection',
                'totalItems' => $statuses->total(),
                // Latest items order by desc (the first on this collection is the latest published)
                'first' => $statuses->url(0),
                // First items, order by desc (the last item on this collection is the first ever published)
                'last' => $statuses->url($statuses->lastPage()),
            ]);
        }
        $collection = new OutboxCollection($statuses);
        $collection->user = $user;
        return $collection;
    }
}
