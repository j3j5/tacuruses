<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use ActivityPhp\Type;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
use App\Services\ActivityPub\Verifier;
use App\Traits\SendsSignedRequests;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use phpseclib3\Crypt\RSA;
use Tests\TestCase;

class SendsSignedRequestsTest extends TestCase
{
    use WithFaker;

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_example(): void
    {
        // $actor = new LocalActor;
        $actor = $this->getMockBuilder(LocalActor::class)
            ->onlyMethods(['privateKey', 'publicKey'])
            ->getMock();
        $actor->username = 'actor';
        $actor->inbox = 'https://example.com/actor/inbox';
        $actor->publicKeyId = 'https://example.com/actor#main-key';
        $key = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);

        $actor->privateKey = $key->toString('PKCS1');
        $actor->publicKey = $key->getPublicKey()->toString('PKCS1');

        $trait = new class() {
            use SendsSignedRequests {
                sendSignedPostRequest as public; // make the method public
            }
        };

        $targetActivityId = fake()->url();
        $followActivityId = fake()->url();
        $actorActivityId = fake()->url();

        $activity = Type::create('Accept', [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $targetActivityId . '#accepts/follows/' . fake()->slug(1),
            'actor' => $targetActivityId,
            'object' => [
                'id' => $followActivityId,
                'actor' => $actorActivityId,
                'type' => 'Follow',
                'object' => $targetActivityId,
            ],
        ]);

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

        Http::fake();
        $signer = app(Signer::class);

        $response = $trait->sendSignedPostRequest(
            signer: $signer,
            actorSigning: $actor,
            url: $actor->inbox,
            data: $data,
        );
        /*
                [ // tests/Feature/Http/Middleware/ActivityPub/HttpSignaturesTest.php:267
                    "Date" => "Wed, 28 Jun 2023 21:38:01 GMT",
                    "Host" => "example.com",
                    "Content-Type" => "application/activity+json; profile="http://www.w3.org/ns/activitystreams"",
                    "Digest" => "SHA-256=pWStGHZKWqcsZ6kCY5eoCTfJNg06J7Ad6+lcQEVDsxc=",
                    "Accept" => "application/activity+json",
                    "Signature" => "keyId="https://example.com/actor#main-key"
                                    ,headers="(request-target) date host content-type digest accept"
                                    ,algorithm="rsa-sha256"
                                    ,signature="H4OUtJbd7+9umkhwcUojgxgUIcPD3GerHvo1Z3YNTC34j6lvujTgFoboOENEOlc2hA9yGpPNPzz5A29UaDT/lDeAMCCoFzh9AkZXMAaT41CSep94j9a9eFY6DyR5qhiRqWaybYbYvOBXqxbICxjucduRBL9q31IcmsiatI76xYr61BVK29is2nrX68TQHITzSzbPTRaN2FEvYscJG8hBjHLyofoIVzer6cIf+o4R5HLxgsdMGCl5DHjcR/ksn0tCV3qYWgeREjMxiKPGZrlZSbfiIUqT980XjhXgTMXTgTF4fuufEAaMEGsEkOKADGxi3BBs1Bi+ug53SseqjdFjLw==""
                ] */

        Http::assertSent(function (Request $request) {
            return $request->hasHeader('Digest') &&
                $request->hasHeader('Signature') &&
                !$request->hasHeader('(request-target)');
        });

        $publicKey = $actor->public_key_object;

        /** @var \App\Services\ActivityPub\Verifier $verifier */
        $verifier = app(Verifier::class);
        $this->assertTrue($verifier->verifyRequest($response->transferStats->getRequest(), $publicKey));
    }
}
