<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityPub\ProfileResource;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\ActivityPub\LocalActor $user
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Illuminate\Http\Resources\Json\JsonResource|\Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request, LocalActor $user)
    {
        if ($request->wantsJson()) {
            return $this->activityProfile($user);
        }
        return $this->profile($user);
    }

    /**
     *
     * @param \App\Models\ActivityPub\LocalActor $actor
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Illuminate\Contracts\View\View
     */
    private function profile(LocalActor $actor)
    {
        $actor->load(['notes' => fn ($query) => $query->take(5)->latest()]);
        $actor->loadCount([
            'followers',
            'following',
        ]);
        $actor->notes->loadCount([
            'likes',
            'shares',
            // 'replies',
        ])->transform(fn (LocalNote $note) => $note->setRelation('actor', $actor));
        return view('bots.profile', compact(['actor']));
    }

    /**
     *
     * @param \App\Models\ActivityPub\LocalActor $user
     * @return \App\Http\Resources\ProfileResource
     */
    private function activityProfile(LocalActor $user)
    {
        return new ProfileResource($user);
    }
}
