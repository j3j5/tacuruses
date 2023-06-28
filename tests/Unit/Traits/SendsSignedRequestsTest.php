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
use phpseclib3\Crypt\RSA;
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
        $actor = LocalActor::factory()->create();

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

        // $key = RSA::createKey();
        // Http::preventStrayRequests();
        // Http::fake([
        //     $actor->inbox => Http::response('foobar', 200),
        // ]);
        $response = $trait->sendSignedPostRequest(
            actorSigning: $actor,
            data: $activity->toArray(),
            url: $actor->inbox,
            // signer: app(Signer::class),
            // actorSigning: $this->actor,
        );

        dd($response);
    }
}
