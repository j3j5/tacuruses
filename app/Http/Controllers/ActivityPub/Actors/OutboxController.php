<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use ActivityPhp\Type\Core\OrderedCollection;
use App\Http\Controllers\Controller;
use App\Http\Resources\OutboxCollection;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\Note;
use App\Services\ActivityPub\Context;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutboxController extends Controller
{
    public function __invoke(Request $request, Actor $actor) : JsonResponse|OutboxCollection
    {
        $perPage = 20;
        $ownNotes = $actor->notes()->latest()->select('id');
        $shares = $actor->shared()->latest()->select('target_id as id');
        $noteIds = $ownNotes->union($shares)->fastPaginate($perPage);

        $notes = Note::with(['actor'])
            ->whereIn('id', $noteIds->getCollection()->pluck('id'))
            ->get();

        if ($request->missing(['page'])) {

            $collection = new OrderedCollection();
            $collection->set('@context', Context::ACTIVITY_STREAMS);
            $collection->id = $actor->outbox;
            $collection->totalItems = $noteIds->total();
            // Latest items order by desc (the first on this collection is the latest published)
            $collection->first = $noteIds->url(0);
            // First items, order by desc (the last item on this collection is the first ever published)
            $collection->last = $noteIds->url($noteIds->lastPage());

            return response()->activityJson($collection->toArray());
        }
        $paginator = $noteIds->setCollection($notes);
        $collection = new OutboxCollection($paginator);
        $collection->actor = $actor;
        return $collection;
    }
}
