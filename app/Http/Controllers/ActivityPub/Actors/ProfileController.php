<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Models\ActivityPub\LocalActor;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __invoke(Request $request, LocalActor $user)
    {
        info('wants json => ' . $request->wantsJson());
        if ($request->wantsJson()) {
            return $this->activityProfile($request, $user);
        }
        return $this->profile($request, $user);
    }

    public function profile(Request $request, LocalActor $user)
    {
        return view('bots.profile', compact(['user']));
    }

    public function activityProfile(Request $request, LocalActor $user)
    {
        return new ProfileResource($user);
    }
}
