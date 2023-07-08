<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __invoke(Request $request, LocalActor $user) : JsonResponse|View
    {
        if ($request->wantsJson()) {
            return response()->activityJson($user->getAPActor()->toArray());
        }

        return $this->profile($user);
    }

    /**
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    private function profile(LocalActor $actor) : View
    {
        // Load 5 latest posts
        $actor->load([
            'notes' => fn ($query) => $query->take(5)->latest(),
        ]);

        // Load profile counts
        $actor->loadCount([
            'followers',
            'following',
        ]);

        // Load notes counts
        $actor->notes->loadCount([
            'likes',
            'shares',
            // 'replies',
        ])->transform(
            fn (LocalNote $note) => $note->setRelation('actor', $actor)
        )->load('mediaAttachments');

        return view('actors.profile', compact(['actor']));
    }
}
