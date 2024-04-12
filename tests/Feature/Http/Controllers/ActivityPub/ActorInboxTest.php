<?php

namespace Tests\Feature\Http\Controllers\ActivityPub;

use App\Enums\ActivityTypes;
use App\Events\LocalNoteLiked;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\RemoteActor;
use App\Services\ActivityPub\Context;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use phpseclib3\Crypt\RSA;
use Tests\TestCase;

class ActorInboxTest extends TestCase
{

    use LazilyRefreshDatabase, WithFaker;

    public function test_like_note()
    {
        $actor = LocalActor::factory()->create();
        $note = LocalNote::factory()
            ->for($actor, 'actor')
            ->public()
            ->create();

        $key = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);

        $actorInfo = $this->actorResponse;
        $actorInfo['publicKey']['publicKeyPem'] = $key->getPublicKey()->toString('PKCS1');

        Http::fake([
            $actorInfo['id'] => Http::response($actorInfo, 200),
            $actorInfo['publicKey']['id'] => Http::response($actorInfo, 200),
            $actorInfo['inbox'] => Http::response('', 202),
        ]);

        Event::fake([
            LocalNoteLiked::class,
        ]);

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json',
        ];

        $data = [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->faker()->url,
            'type' => 'Like',
            'actor' => $actorInfo['id'],
            'object' => route('note.show', [$actor, $note]),
        ];

        $url = route('actor.inbox', [$actor]);
        $headers = $this->sign($key, $actorInfo['publicKey']['id'], $url, json_encode($data), $headers);
        $response = $this->postJson($url, $data, $headers);

        $response->assertAccepted();
        $this->assertCount(1, $note->likes);
        $remoteActor = RemoteActor::where('activityId', $actorInfo['id'])->firstOrFail();
        $this->assertDatabaseHas('activities', [
            'type' => ActivityTypes::LIKE->value,
            'actor_id' => $remoteActor->id,
            'target_id' => $note->id,
            'object' => json_encode($data),
        ]);

        Http::assertSent(function (Request $request) use ($actorInfo) {
            return $request->url() === $actorInfo['inbox'];
        });
        Event::assertDispatched(LocalNoteLiked::class);

    }

    public function test_undo_like_note()
    {
        $actor = LocalActor::factory()->create();
        $note = LocalNote::factory()
            ->for($actor, 'actor')
            ->public()
            ->create();

        $key = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);

        $actorInfo = $this->actorResponse;
        $actorInfo['publicKey']['publicKeyPem'] = $key->getPublicKey()->toString('PKCS1');

        Http::fake([
            $actorInfo['id'] => Http::response($actorInfo, 200),
            $actorInfo['publicKey']['id'] => Http::response($actorInfo, 200),
            $actorInfo['inbox'] => Http::response('', 202),
        ]);

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json',
        ];

        $data = [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->faker()->url,
            'type' => 'Like',
            'actor' => $actorInfo['id'],
            'object' => route('note.show', [$actor, $note]),
        ];

        $url = route('actor.inbox', [$actor]);
        $headers = $this->sign($key, $actorInfo['publicKey']['id'], $url, json_encode($data), $headers);
        $response = $this->postJson($url, $data, $headers);

        $response->assertAccepted();
        $this->assertCount(1, $note->likes);
        $object = $data;
        unset($object['@context']);
        $data = [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->faker()->url,
            'type' => 'Undo',
            'actor' => $actorInfo['id'],
            'object' => $object,
        ];

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json',
        ];

        $url = route('actor.inbox', [$actor]);
        $headers = $this->sign($key, $actorInfo['publicKey']['id'], $url, json_encode($data), $headers);
        $response = $this->postJson($url, $data, $headers);
        $response->assertAccepted();
        $this->assertCount(0, $note->fresh()->likes);
    }

}
