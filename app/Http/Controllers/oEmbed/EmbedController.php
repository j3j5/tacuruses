<?php

namespace App\Http\Controllers\oEmbed;

use App\Http\Controllers\Controller;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmbedController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, LocalActor $actor, LocalNote $note) : View
    {
        $peers = $note->peers()->latest()->take(10)->get()->shuffle();
        return view('actors.embed-note', compact(['note', 'peers']));
    }
}//
