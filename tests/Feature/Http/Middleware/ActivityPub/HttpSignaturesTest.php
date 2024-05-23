<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware\ActivityPub;

use App\Http\Middleware\ActivityPub\VerifySignature;
use App\Models\ActivityPub\RemoteActor;
use App\Services\ActivityPub\Signer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use phpseclib3\Crypt\RSA;
use Tests\TestCase;

class HttpSignaturesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_requests_without_signature_are_unauthorized()
    {
        $headers = [
            'Accept' => 'application/activity+json',
        ];
        $data = [];
        $response = $this->post(route('shared-inbox'), $data, $headers);

        $response->assertUnauthorized()
            ->assertJsonFragment(['message' => 'Missing signature']);
    }

    public function test_requests_without_date_are_unauthorized()
    {
        $headers = [
            'Accept' => 'application/activity+json',
            'Signature' => Str::random(),
        ];
        $data = [];
        $response = $this->post(route('shared-inbox'), $data, $headers);

        $response->assertUnauthorized()
            ->assertJsonFragment(['message' => 'Missing date']);
    }

    public function test_requests_in_the_future_are_unauthorized()
    {
        $keyId = 'https://example.com/users/actor#main-key';
        Http::fake([
            $keyId => $this->actorResponse,
        ]);
        $headers = [
            'Accept' => 'application/activity+json',
            'Signature' => 'keyId="' . $keyId . '",' . Str::random(),
            'Date' => now()->addMinutes(10)->format(Signer::DATE_FORMAT),
        ];

        $data = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'actor' => 'https://example.com/users/actor',
            'type' => 'Create',
            'object' => [
                'type' => 'Note',
                'content' => 'Hello!',
            ],
            'to' => 'https://fedi.example/users/username',
        ];
        $response = $this->post(route('shared-inbox'), $data, $headers);

        $response->assertUnauthorized()
            ->assertJsonFragment(['message' => 'Date is on the future']);
    }

    public function test_requests_in_the_past_are_unauthorized()
    {
        $keyId = 'https://example.com/users/actor#main-key';
        Http::fake([
            $keyId => $this->actorResponse,
        ]);
        $headers = [
            'Accept' => 'application/activity+json',
            'Signature' => 'keyId="' . $keyId . '",' . Str::random(),
            'Date' => now()->subHours(12)->subMinute()->format(Signer::DATE_FORMAT),
        ];
        $data = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'actor' => 'https://example.com/users/actor',
            'type' => 'Create',
            'object' => [
                'type' => 'Note',
                'content' => 'Hello!',
            ],
            'to' => 'https://fedi.example/users/username',
        ];
        $response = $this->post(route('shared-inbox'), $data, $headers);

        $response->assertUnauthorized()
            ->assertJsonFragment(['message' => 'Request date is too old']);
    }

    public function test_requests_wrong_signature_format_are_unauthorized()
    {
        $keyId = 'https://example.com/users/actor#main-key';
        Http::fake([
            $keyId => $this->actorResponse,
        ]);
        $headers = [
            'Accept' => 'application/activity+json',
            'Signature' => 'keyId="' . $keyId . '",' . Str::random(),
            'Date' => now()->format(Signer::DATE_FORMAT),
        ];

        $data = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'actor' => 'https://example.com/users/actor',
            'type' => 'Create',
            'object' => [
                'type' => 'Note',
                'content' => 'Hello!',
            ],
            'to' => 'https://fedi.example/users/username',
        ];
        $response = $this->post(route('shared-inbox'), $data, $headers);

        $response->assertUnauthorized()
            ->assertJsonFragment(['message' => 'Wrong signature 2']);
    }

    public function test_requests_missing_digest_are_unauthorized()
    {
        $keyId = 'https://example.com/users/actor#main-key';
        Http::fake([
            $keyId => $this->actorResponse,
        ]);
        $headers = [
            'Accept' => 'application/activity+json',
            'Signature' => 'keyId="' . $keyId . '",",headers="(request-target) host date",signature="Y2FiYWIxNGRiZDk4ZA=="',
            'Date' => now()->format(Signer::DATE_FORMAT),
        ];

        $data = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'actor' => 'https://example.com/users/actor',
            'type' => 'Create',
            'object' => [
                'type' => 'Note',
                'content' => 'Hello!',
            ],
            'to' => 'https://fedi.example/users/username',
        ];
        $response = $this->post(route('shared-inbox'), $data, $headers);

        $response->assertUnauthorized()
            ->assertJsonFragment(['message' => 'Digest does not match']);
    }

    public function test_requests_from_non_valid_keyId()
    {
        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json; profile="http://www.w3.org/ns/activitystreams"',
            'Signature' => 'keyId="ABC",headers="(request-target) host date digest",signature="Y2FiYW...IxNGRiZDk4ZA=="',
            'Digest' => 'sha-256=pWStGHZKWqcsZ6kCY5eoCTfJNg06J7Ad6+lcQEVDsxc=',
            'Date' => now()->format(Signer::DATE_FORMAT),
        ];

        $data = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'actor' => 'https://example.com/users/actor',
            'type' => 'Create',
            'object' => [
                'type' => 'Note',
                'content' => 'Hello!',
            ],
            'to' => 'https://fedi.example/users/username',
        ];
        $response = $this->postJson(route('shared-inbox'), $data, $headers);

        $response->assertUnprocessable()
            ->assertJsonFragment(['message' => 'The key id must be a valid URL.']);
    }

    public function test_requests_from_non_existent_actor()
    {
        $keyId = 'https://example.com/users/actor#main-key';
        Http::fake([
            $keyId => Http::response('', 404),
        ]);

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json; profile="http://www.w3.org/ns/activitystreams"',
            'Signature' => 'keyId="' . $keyId . '",headers="(request-target) host date digest",signature="Y2FiYW...IxNGRiZDk4ZA=="',
            'Digest' => 'sha-256=pWStGHZKWqcsZ6kCY5eoCTfJNg06J7Ad6+lcQEVDsxc=',
            'Date' => now()->format(Signer::DATE_FORMAT),
        ];

        $data = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'actor' => 'https://example.com/users/actor',
            'type' => 'Create',
            'object' => [
                'type' => 'Note',
                'content' => 'Hello!',
            ],
            'to' => 'https://fedi.example/users/username',
        ];
        $response = $this->postJson(route('shared-inbox'), $data, $headers);

        $response->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Actor cannot be found']);
    }

    public function test_requests_from_different_actor_than_signature()
    {
        $actorId = 'https://example.com/different-actor';
        $keyId = $actorId . '#main-key';
        $actorInfo = [
            'id' => $actorId,
            'type' => 'Person',
            'preferredUsername' => fake()->userName(),
            'name' => fake()->name(),
            // 'summary' => '',
            'url' => fake()->url(),
            'icon.url' => fake()->imageUrl(),
            'image.url' => fake()->imageUrl(),
            'inbox' => fake()->url(),
            'endpoints' => [
                'sharedInbox' => fake()->url(),
            ],
            'publicKey' => [
                'id' => $keyId,
                'publicKeyPem' => Str::random(),
            ],
        ];

        Http::fake([
            $actorId => Http::response($actorInfo, 200),
            $keyId => Http::response($actorInfo, 200),
        ]);

        $signature = 'abc';

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json; profile="http://www.w3.org/ns/activitystreams"',
            'Signature' => 'keyId="' . $keyId . '",headers="(request-target) host date digest",signature="' . $signature . '"',
            'Digest' => 'sha-256=pWStGHZKWqcsZ6kCY5eoCTfJNg06J7Ad6+lcQEVDsxc=',
            'Date' => now()->format(Signer::DATE_FORMAT),
        ];

        $data = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'actor' => 'https://example.com/users/actor',
            'type' => 'Create',
            'object' => [
                'type' => 'Note',
                'content' => 'Hello!',
            ],
            'to' => 'https://fedi.example/users/username',
        ];
        $response = $this->postJson(route('shared-inbox'), $data, $headers);

        $response->assertUnauthorized()
            ->assertJsonFragment([
                'message' => 'Actors do not match',
            ]);
    }

    public function test_request_with_invalid_signature()
    {
        $key = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);

        $actorInfo = $this->actorResponse;
        $actorInfo['publicKey']['publicKeyPem'] = $key->getPublicKey()->toString('PKCS1');

        Http::fake([
            $actorInfo['id'] => Http::response($actorInfo, 200),
            $actorInfo['publicKey']['id'] => Http::response($actorInfo, 200),
        ]);

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json; profile="http://www.w3.org/ns/activitystreams"',
        ];

        $data = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'actor' => $actorInfo['id'],
            'type' => 'Create',
            'object' => [
                'type' => 'Note',
                'content' => 'Hello!',
            ],
            'to' => 'https://fedi.example/users/username',
        ];

        $headers = $this->sign($key, $actorInfo['publicKey']['id'], route('shared-inbox'), json_encode($data), $headers);
        $headers['Signature'] = mb_substr($headers['Signature'], 0, -2) . '"';
        $response = $this->postJson(route('shared-inbox'), $data, $headers);

        $response->assertUnauthorized()
            ->assertJsonFragment([
                'message' => 'Unable to verify given signature',
            ]);
    }

    public function test_request_with_valid_signature()
    {
        // Create fake route
        Route::post('testing-middleware-route', fn () => 'all good!')
            ->middleware(VerifySignature::class);

        $key = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);

        $actorInfo = $this->actorResponse;
        $actorInfo['publicKey']['publicKeyPem'] = $key->getPublicKey()->toString('PKCS1');

        Http::fake([
            $actorInfo['id'] => Http::response($actorInfo, 200),
            $actorInfo['publicKey']['id'] => Http::response($actorInfo, 200),
        ]);

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json; profile="http://www.w3.org/ns/activitystreams"',
        ];

        $data = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'actor' => $actorInfo['id'],
            'type' => 'Create',
            'object' => [
                'type' => 'Note',
                'content' => 'Hello!',
            ],
            'to' => 'https://fedi.example/users/username',
        ];

        $headers = $this->sign($key, $actorInfo['publicKey']['id'], url('/testing-middleware-route'), json_encode($data), $headers);
        $response = $this->postJson(url('/testing-middleware-route'), $data, $headers);

        $response->assertOk();
    }

    public function test_request_with_blind_key_rotation()
    {
        // Create fake route
        Route::post('testing-middleware-route', fn () => 'all good!')
            ->middleware(VerifySignature::class);

        $oldKey = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
        $newKey = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);

        // Store remoteActor with and "old" key
        RemoteActor::factory()
            ->withPublicKey($oldKey->getPublicKey()->toString('PKCS1'))
            ->create([
                'activityId' => $this->actorResponse['id'],
                'publicKeyId' => $this->actorResponse['publicKey']['id'],
            ]);

        // Return the new key when retrieving actor keyId
        $actorInfo = $this->actorResponse;
        $actorInfo['publicKey']['publicKeyPem'] = $newKey->getPublicKey()->toString('PKCS1');
        Http::fake([
            $actorInfo['id'] => Http::response($actorInfo, 200),
            $actorInfo['publicKey']['id'] => Http::response($actorInfo, 200),
        ]);

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json; profile="http://www.w3.org/ns/activitystreams"',
        ];

        $data = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'actor' => $actorInfo['id'],
            'type' => 'Create',
            'object' => [
                'type' => 'Note',
                'content' => 'Hello!',
            ],
            'to' => 'https://fedi.example/users/username',
        ];

        // Sign the request with the new key
        $headers = $this->sign($newKey, $actorInfo['publicKey']['id'], url('/testing-middleware-route'), json_encode($data), $headers);
        $response = $this->postJson(url('/testing-middleware-route'), $data, $headers);

        $response->assertOk();
    }

    public function test_request_with_signed_by_different_key_than_locally_cached_and_remotely_returned()
    {
        // Create fake route
        Route::post('testing-middleware-route', fn () => 'all good!')
            ->middleware(VerifySignature::class);

        $oldKey = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
        $newKey = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
        $thirdKey = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
        $remoteActor = RemoteActor::factory()
            ->withPublicKey($oldKey->getPublicKey()->toString('PKCS1'))
            ->create(['activityId' => $this->actorResponse['id']]);

        $actorInfo = $this->actorResponse;
        $actorInfo['publicKey']['publicKeyPem'] = $newKey->getPublicKey()->toString('PKCS1');

        Http::fake([
            $actorInfo['id'] => Http::response($actorInfo, 200),
            $actorInfo['publicKey']['id'] => Http::response($actorInfo, 200),
        ]);

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json; profile="http://www.w3.org/ns/activitystreams"',
        ];

        $data = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'actor' => $actorInfo['id'],
            'type' => 'Create',
            'object' => [
                'type' => 'Note',
                'content' => 'Hello!',
            ],
            'to' => 'https://fedi.example/users/username',
        ];

        $headers = $this->sign($thirdKey, $actorInfo['publicKey']['id'], url('/testing-middleware-route'), json_encode($data), $headers);
        $response = $this->postJson(url('/testing-middleware-route'), $data, $headers);

        $response->assertUnauthorized();
    }

}
