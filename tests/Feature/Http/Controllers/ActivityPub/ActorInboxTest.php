<?php

namespace Tests\Feature\Http\Controllers\ActivityPub;

use ActivityPhp\Type;
use App\Enums\ActivityTypes;
use App\Events\LocalActorFollowed;
use App\Events\LocalActorUnfollowed;
use App\Events\LocalNoteLiked;
use App\Events\LocalNoteShared;
use App\Models\ActivityPub\Activity;
use App\Models\ActivityPub\Follow;
use App\Models\ActivityPub\Like;
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

    public function test_follow()
    {
        /** @var \App\Models\ActivityPub\LocalActor $localActor */
        $localActor = LocalActor::factory()->create();

        $remoteActorKey = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
        /** @var \App\Models\ActivityPub\RemoteActor $remoteActor */
        $remoteActor = RemoteActor::factory()
            ->withPublicKey($remoteActorKey->getPublicKey()->toString('PKCS1'))
            ->create();

        $actorInfo = $this->actorResponse;
        $actorInfo['id'] = $remoteActor->activityId;
        $actorInfo['publicKey']['publicKeyPem'] = $remoteActor->publicKey;

        Http::fake([
            $actorInfo['id'] => Http::response($actorInfo, 200),
            $actorInfo['publicKey']['id'] => Http::response($actorInfo, 200),
            $actorInfo['inbox'] => Http::response('', 202),
        ]);

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json',
        ];

        /** @var \ActivityPhp\Type\Extended\Activity\Like $dataLike */
        $data = Type::create(ActivityTypes::FOLLOW->value, [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->faker()->url,
            'actor' => $actorInfo['id'],
            'object' => $localActor->activityId,
        ])->toArray();

        Event::fake([
            LocalActorFollowed::class,
        ]);

        $url = route('actor.inbox', [$localActor]);
        $headers = $this->sign($remoteActorKey, $actorInfo['publicKey']['id'], $url, json_encode($data), $headers);
        $response = $this->postJson($url, $data, $headers);

        $response->assertAccepted();

        $this->assertCount(1, $localActor->followers);
        $this->assertTrue($localActor->followers->contains($remoteActor));
        Event::assertDispatched(LocalActorFollowed::class);
    }

    public function test_undo_follow()
    {
        /** @var \App\Models\ActivityPub\LocalActor $localActor */
        $localActor = LocalActor::factory()->create();

        $remoteActorKey = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
        /** @var \App\Models\ActivityPub\RemoteActor $remoteActor */
        $remoteActor = RemoteActor::factory()
            ->withPublicKey($remoteActorKey->getPublicKey()->toString('PKCS1'))
            ->create();

        $actorInfo = $this->actorResponse;
        $actorInfo['id'] = $remoteActor->activityId;
        $actorInfo['publicKey']['publicKeyPem'] = $remoteActor->publicKey;

        /** @var \ActivityPhp\Type\Extended\Activity\Follow $dataFollow */
        $dataFollow = Type::create(ActivityTypes::FOLLOW->value, [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->faker()->url,
            'actor' => $actorInfo['id'],
            'object' => $localActor->activityId,
        ]);
        /** @var \App\Models\ActivityPub\Activity $activity */
        $activity = Activity::factory()
            ->object($dataFollow)
            ->accepted()
            ->state([
                'actor_id' => $remoteActor->id,
                'target_id' => $localActor->id,
            ])
            ->create();

        Follow::factory()
            ->state(['activityId' => $activity->activityId])
            ->accepted()
            ->for($localActor, 'target')
            ->for($remoteActor, 'actor')
            ->create();

        $object = $dataFollow->toArray();
        unset($object['@context']);
        $dataUndo = [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->faker()->url,
            'type' => ActivityTypes::UNDO->value,
            'actor' => $actorInfo['id'],
            'object' => $object,
        ];

        Http::fake([
            $actorInfo['id'] => Http::response($actorInfo, 200),
            $actorInfo['publicKey']['id'] => Http::response($actorInfo, 200),
            $actorInfo['inbox'] => Http::response('', 202),
        ]);

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json',
        ];

        Event::fake([
            LocalActorUnfollowed::class,
        ]);

        $url = route('actor.inbox', [$localActor]);
        $headers = $this->sign(
            privateKey: $remoteActorKey,
            keyId: $actorInfo['publicKey']['id'],
            url: $url,
            body: json_encode($dataUndo),
            extraHeaders: $headers
        );
        $response = $this->postJson($url, $dataUndo, $headers);

        $response->assertAccepted();
        $localActor->refresh();

        $this->assertCount(0, $localActor->followers);
        $this->assertTrue($localActor->followers->doesntContain($remoteActor));
        Event::assertDispatched(LocalActorUnfollowed::class);
    }

    public function test_like_note()
    {
        /** @var \App\Models\ActivityPub\LocalActor $actor */
        $actor = LocalActor::factory()->create();
        /** @var \App\Models\ActivityPub\LocalNote $note */
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
            'type' => ActivityTypes::LIKE->value,
            'actor' => $actorInfo['id'],
            'object' => route('note.show', [$actor, $note]),
        ];

        $url = route('actor.inbox', [$actor]);
        $headers = $this->sign($key, $actorInfo['publicKey']['id'], $url, json_encode($data), $headers);
        $response = $this->postJson($url, $data, $headers);

        $response->assertAccepted();
        $this->assertCount(1, $note->likes);
        // Remote actor was created
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
        /** @var \App\Models\ActivityPub\LocalActor $actor */
        $actor = LocalActor::factory()->create();
        /** @var \App\Models\ActivityPub\LocalNote $note */
        $note = LocalNote::factory()
            ->for($actor, 'actor')
            ->public()
            ->create();

        $remoteActorKey = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
        /** @var \App\Models\ActivityPub\RemoteActor $remoteActor */
        $remoteActor = RemoteActor::factory()
            ->withPublicKey($remoteActorKey->getPublicKey()->toString('PKCS1'))
            ->create();

        $actorInfo = $this->actorResponse;
        $actorInfo['id'] = $remoteActor->activityId;
        $actorInfo['publicKey']['publicKeyPem'] = $remoteActor->publicKey;

        Http::fake([
            $actorInfo['id'] => Http::response($actorInfo, 200),
            $actorInfo['publicKey']['id'] => Http::response($actorInfo, 200),
            $actorInfo['inbox'] => Http::response('', 202),
        ]);

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json',
        ];

        /** @var \ActivityPhp\Type\Extended\Activity\Like $dataLike */
        $dataLike = Type::create(ActivityTypes::LIKE->value, [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->faker()->url,
            'actor' => $actorInfo['id'],
            'object' => route('note.show', [$actor, $note]),
        ]);
        /** @var \App\Models\ActivityPub\Activity $activity */
        $activity = Activity::factory()
            ->accepted()
            ->type(ActivityTypes::LIKE)
            ->object($dataLike)
            ->state([
                'actor_id' => $remoteActor->id,
                'target_id' => $note->id,
            ])
            ->create();

        Like::factory()
            ->state(['activityId' => $activity->activityId])
            ->for('actor', $remoteActor)
            ->for('target', $note);

        $object = $dataLike->toArray();
        unset($object['@context']);
        $dataUndo = [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->faker()->url,
            'type' => ActivityTypes::UNDO->value,
            'actor' => $actorInfo['id'],
            'object' => $object,
        ];

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json',
        ];

        $url = route('actor.inbox', [$actor]);
        $headers = $this->sign($remoteActorKey, $actorInfo['publicKey']['id'], $url, json_encode($dataUndo), $headers);
        $response = $this->postJson($url, $dataUndo, $headers);

        $response->assertAccepted();
        $this->assertCount(0, $note->fresh()->likes);

        $this->assertDatabaseHas('activities', [
            'type' => ActivityTypes::LIKE->value,
            'actor_id' => $remoteActor->id,
            'target_id' => $note->id,
            'object' => $dataLike->toJson(),
        ]);

        $this->assertDatabaseHas('activities', [
            'type' => ActivityTypes::UNDO->value,
            'actor_id' => $remoteActor->id,
            'target_id' => $note->id,
            'object' => json_encode($dataUndo),
        ]);
    }

    public function test_share_note()
    {
        /** @var \App\Models\ActivityPub\LocalActor $actor */
        $actor = LocalActor::factory()->create();
        /** @var \App\Models\ActivityPub\LocalNote $note */
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
            LocalNoteShared::class,
        ]);

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json',
        ];

        $data = [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->faker()->url,
            'type' => ActivityTypes::ANNOUNCE->value,
            'actor' => $actorInfo['id'],
            'object' => route('note.show', [$actor, $note]),
        ];

        $url = route('actor.inbox', [$actor]);
        $headers = $this->sign($key, $actorInfo['publicKey']['id'], $url, json_encode($data), $headers);
        $response = $this->postJson($url, $data, $headers);

        $response->assertAccepted();
        $this->assertCount(1, $note->shares);
        // Remote actor was created
        $remoteActor = RemoteActor::where('activityId', $actorInfo['id'])->firstOrFail();
        $this->assertDatabaseHas('activities', [
            'type' => ActivityTypes::ANNOUNCE->value,
            'actor_id' => $remoteActor->id,
            'target_id' => $note->id,
            'object' => json_encode($data),
        ]);

        Http::assertSent(function (Request $request) use ($actorInfo) {
            return $request->url() === $actorInfo['inbox'];
        });
        Event::assertDispatched(LocalNoteShared::class);
    }

}
