<?php

namespace Tests\Feature\Http\Controllers\ActivityPub;

use App\Models\ActivityPub\LocalActor;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ActorTest extends TestCase
{
    use LazilyRefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_requests_accepting_html_get_html_note()
    {
        $actor = LocalActor::factory()->create();

        $response = $this->get(route('actor.show', ['actor' => $actor]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertViewIs('actors.profile')
            ->assertViewHas('actor', $actor);
    }

    public function test_requests_accepting_json_get_json_note()
    {
        $actor = LocalActor::factory()->create();

        $response = $this->get(route('actor.show', [$actor]), [
            'Accept' => 'application/activity+json',
        ]);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/activity+json; charset=UTF-8')
            ->assertExactJson($actor->getAPActor()->toArray());
    }
}
