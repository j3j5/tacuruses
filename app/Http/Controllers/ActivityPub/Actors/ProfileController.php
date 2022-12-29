<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Models\ActivityPub\LocalActor;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\ActivityPub\LocalActor $user
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request, LocalActor $user)
    {
        info('wants json => ' . $request->wantsJson());
        if ($request->wantsJson()) {
            return $this->activityProfile($user);
        }
        return $this->profile($user);
    }

    /**
     *
     * @param \App\Models\ActivityPub\LocalActor $user
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    private function profile(LocalActor $user)
    {
        return view('bots.profile', compact(['user']));
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
