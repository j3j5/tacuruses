<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use ActivityPhp\Type\Core\Collection;
use ActivityPhp\Type\Core\CollectionPage;
use App\Http\Controllers\Controller;
use App\Http\Middleware\OnlyRequestsWantJson;
use App\Http\Resources\ActivityPub\RepliesResource;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Services\ActivityPub\Context;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteRepliesController extends Controller
{
    public function __construct()
    {
        $this->middleware(OnlyRequestsWantJson::class);
    }

    public function __invoke(Request $request, LocalActor $actor, LocalNote $note) : RepliesResource|JsonResponse
    {
        if ($request->has('page')) {
            return new RepliesResource($note);
        }

        $collection = new Collection();
        $collection->set('@context', Context::ACTIVITY_STREAMS);
        $collection->set('id', route('note.replies', [$actor, $note]));
        $page = new CollectionPage();
        $page->set('id', route('note.replies', [$actor, $note, 'page' => 1]));
        $page->set('next', route('note.replies', [$actor, $note, 'page' => 1]));
        $page->set('partOf', route('note.replies', [$actor, $note]));
        $page->set('items', []);
        $collection->set('first', $page);

        return response()->activityJson($collection->toArray());
    }
}
