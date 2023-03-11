<?php

namespace App\Http\Controllers\ActivityPub\Actors;

use ActivityPhp\Type\Core\Collection;
use ActivityPhp\Type\Core\CollectionPage;
use App\Http\Controllers\Controller;
use App\Http\Resources\RepliesResource;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Services\ActivityPub\Context;
use Illuminate\Http\Request;

class NoteRepliesController extends Controller
{
    public function __invoke(Request $request, LocalActor $actor, LocalNote $note)
    {
        if ($request->has('page')) {
            return new RepliesResource($note);
        }

        $context = ['@context' => Context::ACTIVITY_STREAMS];
        $collection = new Collection();
        $collection->id = route('note.replies', [$note->actor, $this]);
        $page = new CollectionPage();
        $page->id = route('note.replies', [$note->actor, $this, 'page' => 1]);
        $page->next = route('note.replies', [$note->actor, $this, 'page' => 1]);
        $page->partOf = route('note.replies', [$note->actor, $this]);
        $page->items = [];
        $collection->first = $page;

        return response()->activityJson(array_merge($context, $collection->toArray()));
    }
}
