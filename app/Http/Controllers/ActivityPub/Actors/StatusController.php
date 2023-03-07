<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Http\Resources\NoteResource;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \App\Http\Resources\NoteResource|\Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request, LocalActor $actor, LocalNote $note) : NoteResource | View
    {
        if ($request->wantsJson()) {
            return $this->activityStatus($note);
        }
        return $this->status($note);
    }

    private function activityStatus(LocalNote $note) : NoteResource
    {
        return new NoteResource($note);
    }

    private function status(LocalNote $note) : View
    {
        $peers = $note->peers()->take(10)->get();
        return view('bots.status', compact(['note', 'peers']));
    }
}
