<?php

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Http\Controllers\Controller;
use App\Http\Middleware\OnlyRequestsWantJson;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteStampsController extends Controller
{
    public function __construct()
    {
        $this->middleware(OnlyRequestsWantJson::class);
    }

    /**
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __invoke(Request $request, LocalActor $actor, Quote $quote) : JsonResponse
    {
        return response()->activityJson($quote->getAPObject()->toArray());
    }

}
