<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Instance;

use App\Http\Controllers\Controller;

class NodeInfoController extends Controller
{
    /**
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @return \Illuminate\Http\JsonResponse
     */
    public static function get()
    {
        return response()->json([
            'metadata' => [
                'nodeName' => config('app.name'),
                'software' => [
                    'homepage' => 'https://bots.j3j5.uy',
                    'repo' => 'https://gitlab.com/j3j5/twitter-bots',
                ],
                'config' => ['features' => []],
            ],
            'protocols' => [
                'activitypub',
            ],
            'services' => [
                'inbound' => [],
                'outbound' => [],
            ],
            'software' => [
                'name' => 'j3j5-bots',
                'version' => '1.0',
            ],
            'usage' => [
                'localPosts' => 0,
                'localComments' => 0,
                'users' => [
                    'total' => 1,
                    'activeHalfyear' => 1,
                    'activeMonth' => 1,
                ],
            ],
            'version' => '2.0',
            'openRegistrations' => false,
        ]);
    }

    /**
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Illuminate\Http\JsonResponse
     */
    public function wellKnown()
    {
        return response()->json([
            'links' => [
                [
                    // 'href' => config('federation.nodeinfo.url'),
                    'href' => 'https://bots.remote-dev.j3j5.uy/nodeinfo/2.0',
                    'rel' => 'http://nodeinfo.diaspora.software/ns/schema/2.0',
                ],
            ],
        ]);
    }
}
