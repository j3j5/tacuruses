<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Http\Resources\NoteResource;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \App\Http\Resources\NoteResource|\Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request, LocalActor $actor, LocalNote $note) : NoteResource | View
    {
        if ($request->wantsJson()) {
            return $this->jsonNote($note);
        }
        return $this->viewNote($note);
    }

    private function jsonNote(LocalNote $note) : NoteResource
    {
        return new NoteResource($note);
    }

    private function viewNote(LocalNote $note) : View
    {
        $peers = $note->peers()->inRandomOrder()->take(10)->get();
        return view('bots.note', compact(['note', 'peers']));
    }
}
