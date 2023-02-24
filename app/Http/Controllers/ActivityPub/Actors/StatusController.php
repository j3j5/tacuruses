<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Domain\ActivityPub\Contracts\Actor;
use App\Domain\ActivityPub\Contracts\Note;
use App\Http\Controllers\Controller;
use App\Http\Resources\NoteResource;
use App\Models\ActivityPub\Note as ActivityPubNote;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public Actor $actor;

    /**
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Domain\ActivityPub\Contracts\Actor $actor
     * @param string $status
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request, Actor $actor, string $status)
    {
        // $this->actor = $actor;
        // $note = $this->actor->getNote($status);

        $note = ActivityPubNote::where('actor_id', $actor->id)->where('id', $status)->firstOrFail();
        if ($request->wantsJson()) {
            return $this->activityStatus($note);
        }
        return $this->status($note);
    }

    private function activityStatus(Note $note) : NoteResource
    {
        return new NoteResource($note);
    }

    private function status(Note $note) : View
    {
        $data = ['note' => $note];
        return view('bots.status', $data);
    }
}
