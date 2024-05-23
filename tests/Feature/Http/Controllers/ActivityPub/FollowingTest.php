<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ActivityPub;

use ActivityPhp\Type;
use App\Http\Controllers\ActivityPub\Actors\FollowingController;
use App\Models\ActivityPub\Follow;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\RemoteActor;
use App\Services\ActivityPub\Context;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FollowingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_requests_accepting_html_get_404()
    {
        $remoteActors = RemoteActor::factory()->count(3)->create();
        $localActor = LocalActor::factory()->create();
        $follows = [];
        foreach ($remoteActors as $remoteActor) {
            $follows[] = Follow::factory()
                ->accepted()
                ->for($remoteActor, 'actor')
                ->for($localActor, 'target')
                ->create();
        }

        $response = $this->get(route('actor.following', [$localActor]));
        $response->assertNotFound();
    }

    public function test_requests_accepting_json_get_json_ordered_collection()
    {
        $remoteActors = RemoteActor::factory()->count(3)->create();
        $localActor = LocalActor::factory()->create();
        $follows = [];
        foreach ($remoteActors as $remoteActor) {
            $follows[] = Follow::factory()
                ->accepted()
                ->for($localActor, 'actor')
                ->for($remoteActor, 'target')
                ->create();
        }

        $response = $this->get(route('actor.following', [$localActor]), [
            'Accept' => 'application/activity+json',
        ]);

        $expected = Type::create('OrderedCollectionPage', [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => route('actor.following', [$localActor, 'page' => 1]),
            'totalItems' => count($follows),
            'partOf' => route('actor.following', [$localActor]),
            'orderedItems' => $remoteActors->map(
                fn (RemoteActor $remoteActor) : string => $remoteActor->activityId
            )->toArray(),
        ]);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/activity+json; charset=UTF-8')
            ->assertExactJson($expected->toArray());
    }

    public function test_requests_for_when_following_is_multipage()
    {
        $remoteActors = RemoteActor::factory()->count(FollowingController::PER_PAGE + random_int(1, 5))->create();
        $localActor = LocalActor::factory()->create();
        $follows = [];
        foreach ($remoteActors as $remoteActor) {
            $follows[] = Follow::factory()
                ->accepted()
                ->for($localActor, 'actor')
                ->for($remoteActor, 'target')
                ->create();
        }

        $response = $this->get(route('actor.following', [$localActor]), [
            'Accept' => 'application/activity+json',
        ]);

        $expected = [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $localActor->following_url,
            'type' => 'OrderedCollection',
            'totalItems' => count($follows),
            'first' => route('actor.following', [$localActor, 'page' => 1]),
            'last' => route('actor.following', [$localActor, 'page' => 2]),
            'items' => [],
            'orderedItems' => [],
        ];

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/activity+json; charset=UTF-8')
            ->assertExactJson($expected);
    }

    public function test_requests_different_page_for_following()
    {
        $extra = random_int(1, 5);
        $remoteActors = RemoteActor::factory()->count(FollowingController::PER_PAGE + $extra)->create();
        $localActor = LocalActor::factory()->create();
        $follows = [];
        foreach ($remoteActors as $remoteActor) {
            $follows[] = Follow::factory()
                ->accepted()
                ->for($localActor, 'actor')
                ->for($remoteActor, 'target')
                ->create();
        }

        $response = $this->get(route('actor.following', [$localActor, 'page' => 2]), [
            'Accept' => 'application/activity+json',
        ]);

        $expected = [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $localActor->following_url . '?page=2',
            'type' => 'OrderedCollectionPage',
            'totalItems' => count($follows),
            'first' => route('actor.following', [$localActor, 'page' => 1]),
            'last' => route('actor.following', [$localActor, 'page' => 2]),
            'prev' => route('actor.following', [$localActor, 'page' => 1]),
            'current' => route('actor.following', [$localActor, 'page' => 2]),
            'partOf' => route('actor.following', [$localActor]),
            'items' => [],
            'orderedItems' => $remoteActors->skip(FollowingController::PER_PAGE)->map(
                fn (RemoteActor $remoteActor) : string => $remoteActor->activityId
            )->values(),
        ];

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/activity+json; charset=UTF-8')
            ->assertExactJson($expected);
    }
}
