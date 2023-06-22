<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __invoke(Request $request, LocalActor $actor, LocalNote $note) : JsonResponse | View
    {
        if ($request->wantsJson()) {
            return $this->jsonNote($note);
        }
        return $this->viewNote($note);
    }

    private function jsonNote(LocalNote $note) : JsonResponse
    {
        return response()->activityJson($note->getAPNote()->toArray());
    }

    private function viewNote(LocalNote $note) : View
    {
        $peers = $note->peers()->latest()->take(10)->get()->shuffle();
        return view('actors.note', compact(['note', 'peers']));
    }
}
