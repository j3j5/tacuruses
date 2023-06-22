<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Http\Middleware\OnlyRequestsWantJson;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware(OnlyRequestsWantJson::class);
    }

    /**
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\ActivityPub\LocalActor $actor
     * @param \App\Models\ActivityPub\LocalNote $note
     * @throws \Exception
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, LocalActor $actor, LocalNote $note) : JsonResponse
    {
        $activity = $note->getAPActivity();

        return response()->activityJson($activity->toArray());
    }
}
