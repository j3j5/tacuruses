<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ActivityPub\LocalActor;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $actors = LocalActor::inRandomOrder()->simplePaginate(9);

        return view('home', compact(['actors']));
    }
}
