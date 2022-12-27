<?php

namespace App\Http\Controllers\ActivityPub\Instance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SharedInboxController extends Controller
{
    public function __invoke(Request $request)
    {
        info(__CLASS__, ['request' => $request]);
        abort(418);
    }
}
