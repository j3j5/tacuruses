<?php

namespace Tests\Unit\Traits;

use ActivityPhp\Type;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
use App\Traits\SendsSignedRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendsSignedRequestsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_example()
    {
        // $actor = LocalActor::factory()->create();
        $actor = $this->getMockBuilder(LocalActor::class)
            ->onlyMethods(['privateKey', 'publicKey'])
            ->getMock();
        $actor->username = 'actor';
        $actor->inbox = 'https://example.com/actor/inbox';
        $actor->key_id = 'https://example.com/actor#main-key';
        $actor->public_key =
'-----BEGIN RSA PUBLIC KEY-----
MIIBCgKCAQEAuHmi4pMej19A/rYOJ43w4jqspF0Rgbeu2/F0cA6+GTJ2zalRtkFV
CZO9D5a9vBl2FkllSUK+V2p8RBDjXyHHPVv5+tuEZ0fBOBMNQ6UGHtRpGrYoYCUl
M5h4pLFqF/EUA5rOsfSiJ8pTkHBL7P1zENk65Ab9zbQb/ucSMM9XUHTivg3WlQgZ
pJonQMqn/ERnFxPktxtkjU7N+g/0h77tMrWzsvTT6RegMI9QJAEQl2HuakLQ5m+C
l8gM7F/k+r07FpNjO8klPAj741j7Tow5jUD1piFpu7k3rndjXNmpsr6LQqzAqUnt
YeELtaGKTQ9El0g3uUWLB/F75g98KMw5EQIDAQAB
-----END RSA PUBLIC KEY-----
';
        $actor->private_key =
'-----BEGIN RSA PRIVATE KEY-----
MIIEogIBAAKCAQEAuHmi4pMej19A/rYOJ43w4jqspF0Rgbeu2/F0cA6+GTJ2zalR
tkFVCZO9D5a9vBl2FkllSUK+V2p8RBDjXyHHPVv5+tuEZ0fBOBMNQ6UGHtRpGrYo
YCUlM5h4pLFqF/EUA5rOsfSiJ8pTkHBL7P1zENk65Ab9zbQb/ucSMM9XUHTivg3W
lQgZpJonQMqn/ERnFxPktxtkjU7N+g/0h77tMrWzsvTT6RegMI9QJAEQl2HuakLQ
5m+Cl8gM7F/k+r07FpNjO8klPAj741j7Tow5jUD1piFpu7k3rndjXNmpsr6LQqzA
qUntYeELtaGKTQ9El0g3uUWLB/F75g98KMw5EQIDAQABAoIBAAVBcOSt3pRk2TYQ
g394vaTCWW4O2Tt1+1k4jDHPl7ahz4ztLp7nxleRIhUmPSZVXt4Ebgpr6H6W0e7C
AOpSTOn4e3scQy3Kl2yErW9wjYe2Ez7/ss+Ha2OIi+JcTBqdw41VPR+IidCipOYT
7vPT5jH0EGeT8+nnAFevJ19k3tbTyy9zEUqjV8F+4TYxIIGt8yMT7fHGSSsLkEda
dRVaNfCk3xc9ekatB8DLRiuObtrI/el0dKpRPODY/u6nABoRJ8jOMTYVUE8rknZw
TECnkfvstMXF8imjaEvQkSN/kTKNy0QNi4WeFkt9BsPBu8mNbP5CWz2yNFHoAGpI
akUB9YECgYEA952CMsCDFQYoWeHvIXofX8CyskHp/YpyXxBpzebOMG3B0nE9Cmer
xudZ5Jg0ChOT3OjtF2P4llO6fGj2ByiP/O134ACHW/FnqZ82MC+eSho7fafmEjRG
n3i7FSLKeDtiPbDUmaQbVfOTUUAikLgK0MwNQAuJwwQqTE8lDlgSj1ECgYEAvrjJ
phk6J2GRDUVPDAchIQqm6gB7QrwUuTy9NgczXdu4s8XHalSqH4tTausGtY5mioLp
ocCWmkNIKE6iv8SIiyt+MdYnFPX7Q49JlhVN/t/yF5dNKGLhnE9TQIYpN5XVkQgB
ygDvDa1dNEJAbhGNR/HqbtYjBbdC5mca487yHcECgYAYerLXb57F4lD06dgpOBiH
79X6t3d5gElkSowbNfkmYoNp2ZYOREeAJ3kEl2N1p4/TpBdkW5bEzcw+5mWEOada
euOB+qtnFIuKAlpqExsT+lntRz8Ah7h/pYDhSwo3Lq+8p5GtCsNFEQp8WtnP8tHm
6HVa4okhBXVlC+H+9AgJsQKBgATcJJdM8URrogyahYci718uLE6uMHXk4QpPV+r2
iAKORMif85LsEWG4ZSnGT2d093v0Fgv/IldP+5i7WD5Wvbv+IbsXX+uS0RJJpnBi
npguELc1LcwnqOQYyWBNiuNVuBl4AMOq/mx0zHk2bvnfDKCWs4ibgEz2/IdufSrs
7RKBAoGAR7e2KGC1nYXikni351zo8bR4F+Gr9o2dhc1ZfB+FcK23mnn+zo+5Eugq
8OrhdKX/zBalPHojp52Ww37PdjLl5bpC7K7fixvm/XAj3XgmeH6UVZI9iW6ly9bW
r2IS3loMUzHCLoGy88Qu1r0WjzKz+pTBN1sD2Upch/wYF9A+VPE=
-----END RSA PRIVATE KEY-----';

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

        Http::preventStrayRequests();
        Http::fake();

        $response = $trait->sendSignedPostRequest(
            actorSigning: $actor,
            data: $data,
            url: $actor->inbox,
            // signer: app(Signer::class),
            // actorSigning: $this->actor,
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

        $sentHeaders = $response->transferStats->getRequest()->getHeaders();

        $this->assertArrayHasKey('Digest', $sentHeaders);
        $this->assertArrayHasKey('Signature', $sentHeaders);
        $this->assertArrayNotHasKey('(request-target)', $sentHeaders);
    }
}
