<?php

declare(strict_types=1);

namespace Tests;

use ActivityPhp\Type;
use App\Domain\ActivityPub\Mastodon\Create;
use App\Models\ActivityPub\Actor;
use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use phpseclib3\Crypt\Common\PrivateKey;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use WithFaker;

    protected array $actorResponse = [
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
            'sharedInbox' => 'https://example.com/inbox',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Avoid HTTP requests to the outside, they should be faked
        Http::preventStrayRequests();

        //
        // Storage::fake('local');

        if (!env('ENABLE_LOGGING_ON_TESTS', false)) {
            Log::shouldReceive('channel')->andReturnSelf();
            Log::shouldReceive('debug', 'info', 'notice', 'error', 'warning', 'alert', 'critical', 'emergency');
        }
    }

    protected function sign(PrivateKey $privateKey, string $keyId, string $url, ?string $body = null, array $extraHeaders = []) : array
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
        $signature = base64_encode($privateKey->sign($stringToSign));
        $signatureHeader = 'keyId="' . $keyId . '",headers="' . $signedHeaders . '",algorithm="rsa-sha256",signature="' . $signature . '"';
        unset($headers['(request-target)']);
        $headers['Signature'] = $signatureHeader;

        return $headers;
    }

    protected function generateCreateActivity(Actor $actor, string $note = null) : Create
    {
        if ($note === null) {
            $note = $this->faker->sentences(nb: random_int(2, 10), asText: true);
        }
        $noteActivityId = $actor->activityId . '/statuses/' . random_int(10000, 100000);
        $delay = 5;

        return Type::create('Create', [
            '@context' => [Context::ACTIVITY_STREAMS],
            'id' => $noteActivityId . 'activity',
            'actor' => $actor->activityId,
            'published' => now()->subSeconds($delay),
            'to' => [
                $actor->activityId . 'followers',
            ],
            'cc' => [
                Context::ACTIVITY_STREAMS_PUBLIC,
                // $localActor->activityId,
            ],
            'object' => Type::create('Note', [
                'id' => $noteActivityId,
                'inReplyTo' => null,
                'published' => now()->subSeconds($delay),
                'url' => $noteActivityId,
                'attributedTo' => $actor->activityId,
                'to' => [
                    $actor->activityId . 'followers',
                ],
                'cc' => [
                    Context::ACTIVITY_STREAMS_PUBLIC,
                ],
                'sensitive' => false,
                'content' => $note,
                'contentMap' => [$this->faker->languageCode() => $note],
                'attachment' => [],
                'tag' => [
                ],
                'replies' => [
                    Type::create('Collection', [
                        'id' => $noteActivityId . 'replies',
                        'first' => Type::Create('CollectionPage', [
                            'next' => $noteActivityId . 'replies?page=2',
                            'partOf' => $noteActivityId . 'replies',
                            'items' => [],
                        ]),
                    ]),
                ],
            ]),
            'signature' => [
                'type' => 'RsaSignature2017',
                'creator' => $actor->activityId,
                'created' => now()->subSeconds($delay)->toJSON(),
                // TODO: implement proper linked data signatures
                'signatureValue' => Str::random(64),
            ],
        ]);
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
