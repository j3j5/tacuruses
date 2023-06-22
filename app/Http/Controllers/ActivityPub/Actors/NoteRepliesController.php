<?php

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

        $context = ['@context' => Context::ACTIVITY_STREAMS];
        $collection = new Collection();
        $collection->set('id', route('note.replies', [$note->actor, $this]));
        $page = new CollectionPage();
        $page->set('id', route('note.replies', [$note->actor, $this, 'page' => 1]));
        $page->set('next', route('note.replies', [$note->actor, $this, 'page' => 1]));
        $page->set('partOf', route('note.replies', [$note->actor, $this]));
        $page->set('items', []);
        $collection->set('first', $page);

        return response()->activityJson(array_merge($context, $collection->toArray()));
    }
}
