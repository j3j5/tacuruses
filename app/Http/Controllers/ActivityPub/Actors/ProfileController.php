<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityPub\ProfileResource;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __invoke(Request $request, LocalActor $user) : ProfileResource|View
    {
        if ($request->wantsJson()) {
            return new ProfileResource($user);
        }

        return $this->profile($user);
    }

    /**
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    private function profile(LocalActor $actor) : View
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
        ])->transform(
            fn (LocalNote $note) => $note->setRelation('actor', $actor)
        );

        return view('bots.profile', compact(['actor']));
    }

}
