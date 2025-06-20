<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\Mastodon\Instance;

use App\Http\Controllers\Controller;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PeersController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $cacheTTLmin = now()->addHour();
        $cacheTTLmax = now()->addHours(24);

        $domains = Cache::flexible(
            'instance-peers',
            [$cacheTTLmin, $cacheTTLmax],
            fn () => RemoteActor::distinct('sharedInbox')
                ->get('sharedInbox')
                ->map(fn (RemoteActor $remoteActor) => parse_url((string) $remoteActor->sharedInbox, PHP_URL_HOST))
                ->filter()
                ->all()
        );

        return response()->json($domains);
    }
}
