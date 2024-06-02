<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ActivityPub\LocalActor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request) : View
    {
        $actors = LocalActor::withCount([
            'followers',
            'following',
        ])->latest()->get();

        return view('home', compact(['actors']));
    }
}
