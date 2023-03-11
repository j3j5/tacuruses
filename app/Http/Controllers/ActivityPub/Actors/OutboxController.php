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
    public function __invoke(Request $request, LocalActor $actor) : OutboxCollection
    {
        $perPage = 20;
        $notes = $actor->notes()->paginate($perPage);

        if ($request->missing(['page']) && $notes->total() > $perPage) {
            return response()->activityJson([
                '@context' => Context::ACTIVITY_STREAMS,
                'id' => $actor->outboxUrl(),
                'type' => 'OrderedCollection',
                'totalItems' => $notes->total(),
                // Latest items order by desc (the first on this collection is the latest published)
                'first' => $notes->url(0),
                // First items, order by desc (the last item on this collection is the first ever published)
                'last' => $notes->url($notes->lastPage()),
            ]);
        }
        $collection = new OutboxCollection($notes);
        $collection->actor = $actor;
        return $collection;
    }
}
