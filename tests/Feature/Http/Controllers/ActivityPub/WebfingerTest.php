<?php

namespace Tests\Feature\Http\Controllers\ActivityPub;

use App\Models\ActivityPub\LocalActor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use function Safe\parse_url;

use Tests\TestCase;

class WebfingerTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_json_requests_get_404()
    {
        $actor = LocalActor::factory()->create();
        $host = parse_url(config('app.url'), PHP_URL_HOST);

        $response = $this->get(
            route('webfinger', ['resource' => "acct:{$actor->username}@$host"]),
        );

        $response->assertNotFound();
    }

    public function test_searching_existing_actor()
    {
        $actor = LocalActor::factory()->create();
        $host = parse_url(config('app.url'), PHP_URL_HOST);

        $response = $this->get(
            route('webfinger', ['resource' => "acct:{$actor->username}@$host"]),
            ['Accept' => 'application/json']
        );

        $expected = [
            'subject' => "acct:{$actor->username}@$host",
            'aliases' => [
                route('actor.show', [$actor]),
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
        ];

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/jrd+json; charset=UTF-8')
            ->assertExactJson($expected);
    }

    public function test_search_non_existant_account()
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST);
        $response = $this->get(
            route('webfinger', ['resource' => "acct:unknown@$host"]),
            ['Accept' => 'application/json']
        );
        $response->assertNotFound()
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_search_on_wrong_host()
    {
        $actor = LocalActor::factory()->create();

        $response = $this->get(
            route('webfinger', ['resource' => "acct:{$actor->username}@example.org"]),
            ['Accept' => 'application/json']
        );
        $response->assertNotFound()
           ->assertHeader('Content-Type', 'application/json');
    }

    public function test_search_malformed_resource()
    {
        $actor = LocalActor::factory()->create();
        $host = parse_url(config('app.url'), PHP_URL_HOST);

        // Leading @
        $response = $this->get(
            route('webfinger', ['resource' => "acct:@{$actor->username}@$host"]),
            ['Accept' => 'application/json']
        );
        $response->assertBadRequest()
           ->assertHeader('Content-Type', 'application/json');

        // Missing host
        $response = $this->get(
            route('webfinger', ['resource' => "acct:{$actor->username}"]),
            ['Accept' => 'application/json']
        );
        $response->assertBadRequest()
           ->assertHeader('Content-Type', 'application/json');

        // No username
        $response = $this->get(
            route('webfinger', ['resource' => "acct:@$host"]),
            ['Accept' => 'application/json']
        );
        $response->assertNotFound()
           ->assertHeader('Content-Type', 'application/json');

        // Missing host with trailing @
        $response = $this->get(
            route('webfinger', ['resource' => "acct:{$actor->username}@"]),
            ['Accept' => 'application/json']
        );
        $response->assertNotFound()
           ->assertHeader('Content-Type', 'application/json');

        // no acct
        $response = $this->get(
            route('webfinger', ['resource' => "{$actor->username}@host"]),
            ['Accept' => 'application/json']
        );
        $response->assertBadRequest()
           ->assertHeader('Content-Type', 'application/json');

        // random
        $response = $this->get(
            route('webfinger', ['resource' => Str::random(32)]),
            ['Accept' => 'application/json']
        );
        $response->assertBadRequest()
           ->assertHeader('Content-Type', 'application/json');
    }

}
