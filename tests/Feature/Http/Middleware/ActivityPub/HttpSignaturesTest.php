<?php

namespace Tests\Feature\Http\Middleware\ActivityPub\Federation;

use App\Http\Middleware\ActivityPub\VerifySignature;
use App\Services\ActivityPub\NewSigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\RSA;
use RuntimeException;
use Tests\TestCase;

class HttpSignaturesTest extends TestCase
{
    use RefreshDatabase;

    private array $actorResponse = [
        '@context' => [
            'https://www.w3.org/ns/activitystreams',
            'https://w3id.org/security/v1',
        ],
        'id' => 'https://example.com/users/actor',
        'type' => 'Person',
        'name' => 'The Actor',
        'following' => 'https://example.com/users/actor/following',
        'followers' => 'https://example.com/users/actor/followers',
        'inbox' => 'https://example.com/users/actor/inbox',
        'outbox' => 'https://example.com/users/actor/outbox',
        'preferredUsername' => 'actor',
        'publicKey' => [
            'id' => 'https://example.com/users/actor#main-key',
            'owner' => 'https://example.com/users/actor',
            'publicKeyPem' => "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAuHmi4pMej19A/rYOJ43w4jqspF0Rgbeu2/F0cA6+GTJ2zalRtkFV\nCZO9D5a9vBl2FkllSUK+V2p8RBDjXyHHPVv5+tuEZ0fBOBMNQ6UGHtRpGrYoYCUl\nM5h4pLFqF/EUA5rOsfSiJ8pTkHBL7P1zENk65Ab9zbQb/ucSMM9XUHTivg3WlQgZ\npJonQMqn/ERnFxPktxtkjU7N+g/0h77tMrWzsvTT6RegMI9QJAEQl2HuakLQ5m+C\nl8gM7F/k+r07FpNjO8klPAj741j7Tow5jUD1piFpu7k3rndjXNmpsr6LQqzAqUnt\nYeELtaGKTQ9El0g3uUWLB/F75g98KMw5EQIDAQAB\n-----END RSA PUBLIC KEY-----",
        ],
        'url' => 'https://example.com/@actor',
        'endpoints' => [
            'sharedInbox' => 'https://hachyderm.io/inbox',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

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
            'Date' => now()->addMinutes(10)->format(NewSigner::DATE_FORMAT),
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
            'Date' => now()->subHours(13)->format(NewSigner::DATE_FORMAT),
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
            'Date' => now()->format(NewSigner::DATE_FORMAT),
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
            'Date' => now()->format(NewSigner::DATE_FORMAT),
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
            'Date' => now()->format(NewSigner::DATE_FORMAT),
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
            'Date' => now()->format(NewSigner::DATE_FORMAT),
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
            'Date' => now()->format(NewSigner::DATE_FORMAT),
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

    private function sign(PrivateKey $privateKey, string $keyId, string $url, ?string $body = null, array $extraHeaders = []) : array
    {
        $digest = null;
        if ($body !== null) {
            // TODO: algo should be dynamic
            $hashAlgo = 'sha256';
            $digest = base64_encode(hash($hashAlgo, $body, true));
        }
        $headers = $this->headersToSign($url, $digest);
        $headers = array_merge($headers, $extraHeaders);
        $stringToSign = $this->stringFromHeaders($headers);
        $signedHeaders = implode(
            ' ',
            array_map('strtolower', array_keys($headers))
        );
        Log::debug('Strign to sign: "' . PHP_EOL . PHP_EOL . $stringToSign . '"' . PHP_EOL);
        $signature = base64_encode($privateKey->sign($stringToSign));
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
            'Date' => now('UTC')->format(NewSigner::DATE_FORMAT),
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
