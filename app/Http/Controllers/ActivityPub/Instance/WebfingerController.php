<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Instance;

use ActivityPhp\Server\Http\WebFinger;
use App\Http\Controllers\Controller;
use App\Models\ActivityPub\LocalActor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use function Safe\preg_match;

class WebfingerController extends Controller
{
    public function __invoke(Request $request) : JsonResponse
    {
        $resource = $request->input('resource');

        if (0 === preg_match('/^acct:(.+)/i', $resource, $match)) {
            abort(400, 'Wrong resource');
        }

        $handle = $match[1];

        if (2 !== count($handleParts = explode('@', $handle))) {
            abort(400, 'Wrong account format');
        }

        $hostname = $handleParts[1];
        if ($request->getHost() !== $hostname) {
            abort(404, 'host not found');
        }

        $preferredUsername = $handleParts[0];
        $user = LocalActor::where('username', $preferredUsername)->firstOrFail();

        $webfinger = new WebFinger([
            'subject' => $resource,
            'aliases' => [
                // 'https://' . request()->getHost() . '/' . $user->username
                // 'https://' . request()->getHost() . '/@' . $user->username,
                // TODO: Add support for aliases on said user object
                // $user->getAliases(),
            ],
            'links' => [
                [
                    'rel' => 'http://webfinger.net/rel/profile-page',
                    'type' => 'text/html',
                    'href' => "https://bots.remote-dev.j3j5.uy/@{$user->username}",
                ],
                [
                    'rel' => 'self',
                    'type' => 'application/activity+json',
                    'href' => route('actor.show', [$user]),
                ],
                [
                    'rel' => 'http://ostatus.org/schema/1.0/subscribe',
                    'template' => "https://$hostname/authorize_interaction?uri={uri}",
                ],
            ],
        ]);

        return response()->jrdJson($webfinger->toArray());
    }
}
