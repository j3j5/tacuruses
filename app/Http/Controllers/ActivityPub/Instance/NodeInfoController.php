<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Instance;

use App\Http\Controllers\Controller;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Support\Facades\Cache;

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
        $cacheTTL = now()->addHour();
        return response()->json([
            'metadata' => [
                'nodeName' => config('app.name'),
                'software' => [
                    'homepage' => config('federation.homepage'),
                    'repo' => 'https://gitlab.com/j3j5/tacuruses',
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
                'name' => config('federation.software_name'),
                'version' => config('instance.software_version'),
            ],
            'usage' => [
                'localPosts' => Cache::remember('local-posts', $cacheTTL, function () {
                    return LocalNote::count();
                }),
                'localComments' => 0,
                'users' => [
                    'total' => Cache::remember('total-users', $cacheTTL, function () {
                        return LocalActor::count();
                    }),
                    'activeHalfyear' => Cache::remember('users-active-6m', $cacheTTL, function () {
                        return LocalActor::whereHas('notes', function ($query) {
                            $query->where('created_at', '>', now()->subMonths(6)->toDateTimeString());
                        })->count();
                    }),
                    'activeMonth' => Cache::remember('users-active-1m', $cacheTTL, function () {
                        return LocalActor::whereHas('notes', function ($query) {
                            $query->where('created_at', '>', now()->subMonth()->toDateTimeString());
                        })->count();
                    }),
                ],
            ],
            'version' => config('app.version' . '1.0.0'),
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
                    'href' => config('federation.homepage'),
                    'rel' => 'http://nodeinfo.diaspora.software/ns/schema/2.0',
                ],
            ],
        ]);
    }
}
