<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Http\Middleware\NoCookies;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct()
    {
        // manual wantsJson() to avoid messing with the request
        $acceptable = explode(separator: ',', string: (string) request()->header('Accept'));
        if (Str::contains(strtolower($acceptable[0]), ['/json', '+json'])) {
            $this->middleware(NoCookies::class);
        }

    }

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
        $notes = $actor->notes()->latest()->simpleFastPaginate(5);

        // Load profile counts
        $actor->loadCount([
            'followers',
            'following',
        ]);

        // Load notes counts
        $notes->loadCount([
            'likes',
            'shares',
            // 'replies',
        ])->transform(
            fn (LocalNote $note) => $note->setRelation('actor', $actor)
        )->load('mediaAttachments');

        return view('actors.profile', compact(['actor', 'notes']));
    }
}
