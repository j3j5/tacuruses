<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FollowersController extends Controller
{
    public function __invoke(Request $request)
    {
        info(__CLASS__, ['request' => $request]);
        abort(418);
    }
}
