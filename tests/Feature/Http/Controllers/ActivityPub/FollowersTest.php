<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ActivityPub;

use ActivityPhp\Type;
use App\Http\Controllers\ActivityPub\Actors\FollowersController;
use App\Models\ActivityPub\Follow;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\RemoteActor;
use App\Services\ActivityPub\Context;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FollowersTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_requests_accepting_html_get_404(): void
    {
        $remoteActors = RemoteActor::factory()->count(3)->create();
        $localActor = LocalActor::factory()->create();
        $follows = [];
        foreach ($remoteActors as $remoteActor) {
            $follows[] = Follow::factory()
                ->accepted()
                ->for($localActor, 'target')
                ->for($remoteActor, 'actor')
                ->create();
        }

        $response = $this->get(route('actor.followers', [$localActor]));
        $response->assertNotFound();
    }

    public function test_requests_accepting_json_get_json_ordered_collection(): void
    {
        $remoteActors = RemoteActor::factory()->count(3)->create();
        $localActor = LocalActor::factory()->create();
        $follows = [];
        foreach ($remoteActors as $remoteActor) {
            $follows[] = Follow::factory()
                ->accepted()
                ->for($localActor, 'target')
                ->for($remoteActor, 'actor')
                ->create();
        }

        $response = $this->get(route('actor.followers', [$localActor]), [
            'Accept' => 'application/activity+json',
        ]);

        $expected = Type::create('OrderedCollectionPage', [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => route('actor.followers', [$localActor, 'page' => 1]),
            'totalItems' => count($follows),
            // 'next' => $this->when(!empty($next), $next),
            // 'prev' => $this->when(!empty($prev), $prev),
            'partOf' => route('actor.followers', [$localActor]),
            'orderedItems' => $remoteActors->map(
                fn (RemoteActor $remoteActor) : string => $remoteActor->activityId
            )->toArray(),
        ]);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/activity+json; charset=UTF-8')
            ->assertExactJson($expected->toArray());
    }

    public function test_requests_for_when_followers_is_multipage(): void
    {
        $remoteActors = RemoteActor::factory()->count(FollowersController::PER_PAGE + random_int(1, 5))->create();
        $localActor = LocalActor::factory()->create();
        $follows = [];
        foreach ($remoteActors as $remoteActor) {
            $follows[] = Follow::factory()
                ->accepted()
                ->for($localActor, 'target')
                ->for($remoteActor, 'actor')
                ->create();
        }

        $response = $this->get(route('actor.followers', [$localActor]), [
            'Accept' => 'application/activity+json',
        ]);

        $expected = [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $localActor->followers_url,
            'type' => 'OrderedCollection',
            'totalItems' => count($follows),
            'first' => route('actor.followers', [$localActor, 'page' => 1]),
            // First items, order by desc (the last item on this collection is the first ever published)
            'last' => route('actor.followers', [$localActor, 'page' => 2]),
            'items' => [],
            'orderedItems' => [],
        ];

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/activity+json; charset=UTF-8')
            ->assertExactJson($expected);
    }

    public function test_requests_different_page_for_followers(): void
    {
        $extra = random_int(1, 5);
        $remoteActors = RemoteActor::factory()->count(FollowersController::PER_PAGE + $extra)->create();
        $localActor = LocalActor::factory()->create();
        $follows = [];
        foreach ($remoteActors as $remoteActor) {
            $follows[] = Follow::factory()
                ->accepted()
                ->for($remoteActor, 'actor')
                ->for($localActor, 'target')
                ->create();
        }

        $response = $this->get(route('actor.followers', [$localActor, 'page' => 2]), [
            'Accept' => 'application/activity+json',
        ]);

        $expected = [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $localActor->followers_url . '?page=2',
            'type' => 'OrderedCollectionPage',
            'totalItems' => count($follows),
            'first' => route('actor.followers', [$localActor, 'page' => 1]),
            'last' => route('actor.followers', [$localActor, 'page' => 2]),
            'prev' => route('actor.followers', [$localActor, 'page' => 1]),
            'current' => route('actor.followers', [$localActor, 'page' => 2]),
            'partOf' => route('actor.followers', [$localActor]),
            'items' => [],
            'orderedItems' => $remoteActors->skip(FollowersController::PER_PAGE)->map(
                fn (RemoteActor $remoteActor) : string => $remoteActor->activityId
            )->values(),
        ];

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/activity+json; charset=UTF-8')
            ->assertExactJson($expected);
    }
}
