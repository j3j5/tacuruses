<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Instance;

use ActivityPhp\Server\Http\WebFinger;
use App\Http\Controllers\Controller;
use App\Http\Requests\WebFingerRequest;
use App\Models\ActivityPub\LocalActor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use function Safe\preg_match;

class WebfingerController extends Controller
{
    public function __invoke(WebFingerRequest $request) : JsonResponse
    {
        $resource = $request->input('resource');

        if (0 === preg_match('/^acct:(.+)/i', $resource, $match)) {
            return response()->json(['message' => 'Wrong resource'], Response::HTTP_BAD_REQUEST);
        }

        $handle = $match[1];

        if (2 !== count($handleParts = explode('@', $handle))) {
            return response()->json(['message' => 'Wrong account format'], Response::HTTP_BAD_REQUEST);
        }

        $hostname = $handleParts[1];
        if ($request->getHost() !== $hostname) {
            return response()->json(['message' => 'Unknown host'], Response::HTTP_NOT_FOUND);
        }

        $preferredUsername = $handleParts[0];
        try {
            $actor = LocalActor::where('username', $preferredUsername)->firstOrFail();
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $webfinger = new WebFinger([
            'subject' => $resource,
            'aliases' => [
                route('actor.show', [$actor]),
                // $user->getAliases(),
            ],
            'links' => [
                [
                    'rel' => 'http://webfinger.net/rel/profile-page',
                    'type' => 'text/html',
                    'href' => route('actor.show', [$actor]),
                ],
                [
                    'rel' => 'self',
                    'type' => 'application/activity+json',
                    'href' => route('actor.show', [$actor]),
                ],
            ],
        ]);

        return response()->jrdJson($webfinger->toArray());
    }
}
