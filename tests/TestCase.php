<?php

namespace Tests;

use App\Services\ActivityPub\Signer;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\Common\PrivateKey;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
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

        Http::preventStrayRequests();
        Storage::fake('local');

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
