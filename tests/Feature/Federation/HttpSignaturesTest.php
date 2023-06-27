<?php

namespace Tests\Feature\Federation;

use App\Http\Middleware\ActivityPub\VerifySignature;
use App\Services\ActivityPub\Signer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use phpseclib3\Crypt\RSA;
use RuntimeException;
use Tests\TestCase;

class HttpSignaturesTest extends TestCase
{
    use RefreshDatabase;

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
        $headers = [
            'Accept' => 'application/activity+json',
            'Signature' => Str::random(),
            'Date' => now()->addMinutes(10)->format(Signer::DATE_FORMAT),
        ];

        $data = [];
        $response = $this->post(route('shared-inbox'), $data, $headers);

        $response->assertUnauthorized()
            ->assertJsonFragment(['message' => 'Date is on the future']);
    }

    public function test_requests_in_the_past_are_unauthorized()
    {
        $headers = [
            'Accept' => 'application/activity+json',
            'Signature' => Str::random(),
            'Date' => now()->subHours(13)->format(Signer::DATE_FORMAT),
        ];
        $data = [];
        $response = $this->post(route('shared-inbox'), $data, $headers);

        $response->assertUnauthorized()
            ->assertJsonFragment(['message' => 'Request date is too old']);
    }

    public function test_requests_wrong_signature_format_are_unauthorized()
    {
        $headers = [
            'Accept' => 'application/activity+json',
            'Signature' => Str::random(),
            'Date' => now()->format(Signer::DATE_FORMAT),
        ];

        $data = [];
        $response = $this->post(route('shared-inbox'), $data, $headers);

        $response->assertUnauthorized()
            ->assertJsonFragment(['message' => 'Wrong signature 2']);
    }

    public function test_requests_missing_digest_are_unauthorized()
    {
        $headers = [
            'Accept' => 'application/activity+json',
            'Signature' => 'keyId="https://example.com/actor#main-key",headers="(request-target) host date",signature="Y2FiYWIxNGRiZDk4ZA=="',
            'Date' => now()->format(Signer::DATE_FORMAT),
        ];

        $data = [];
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
            'actor' => 'https://example.com/actor',
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
        $keyId = 'https://example.com/actor#main-key';
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
            'actor' => 'https://example.com/actor',
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
            'actor' => 'https://example.com/actor',
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

    public function test_request_with_valid_signature()
    {
        // Create fake route
        Route::post('testing-middleware-route', fn () => ' all good!')
            ->middleware(VerifySignature::class);

        $key = RSA::createKey();

        $actorId = 'https://example.com/actor';
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
                'publicKeyPem' => $key->getPublicKey()->toString('PKCS1'),
            ],
        ];

        Http::fake([
            $actorId => Http::response($actorInfo, 200),
            $keyId => Http::response($actorInfo, 200),
        ]);

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json; profile="http://www.w3.org/ns/activitystreams"',
        ];

        $data = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'actor' => $actorId,
            'type' => 'Create',
            'object' => [
                'type' => 'Note',
                'content' => 'Hello!',
            ],
            'to' => 'https://fedi.example/users/username',
        ];
        $headers = $this->sign($key->toString('PKCS1'), $keyId, url('/testing-middleware-route'), json_encode($data), $headers);
        $response = $this->postJson(url('/testing-middleware-route'), $data, $headers);

        $response->assertOk();
    }

    private function sign(string $privateKey, string $keyId, string $url, ?string $body = null, array $extraHeaders = []) : array
    {
        $digest = null;
        if ($body !== null) {
            $digest = base64_encode(hash('sha256', $body, true));
        }
        $headers = $this->headersToSign($url, $digest);
        $headers = array_merge($headers, $extraHeaders);
        $stringToSign = $this->stringFromHeaders($headers);
        $signedHeaders = implode(
            ' ',
            array_map('strtolower', array_keys($headers))
        );
        $key = openssl_pkey_get_private($privateKey);
        openssl_sign($stringToSign, $signature, $key, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($signature);
        $signatureHeader = 'keyId="' . $keyId . '",headers="' . $signedHeaders . '",algorithm="rsa-sha256",signature="' . $signature . '"';
        unset($headers['(request-target)']);
        $headers['Signature'] = $signatureHeader;

        return $headers;
    }

    private function headersToSign(string $url, ?string $digest = null) : array
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path)) {
            throw new RuntimeException('URL does not have a valid path: ' . $url);
        }

        $headers = [
            '(request-target)' => 'post ' . $path,
            'Date' => now('UTC')->format(Signer::DATE_FORMAT),
            'Host' => parse_url($url, PHP_URL_HOST),
            'Content-Type' => 'application/activity+json',
        ];

        if ($digest !== null) {
            $headers['Digest'] = 'SHA-256=' . $digest;
        }

        return $headers;
    }

    private function stringFromHeaders(array $headers) : string
    {
        return implode("\n", array_map(function ($k, $v) {
            return strtolower($k) . ': ' . $v;
        }, array_keys($headers), $headers));
    }

}
