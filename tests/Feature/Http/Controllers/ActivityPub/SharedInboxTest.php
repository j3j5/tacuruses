<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ActivityPub;

use App\Enums\ActivityTypes;
use App\Events\LocalActorMentioned;
use App\Events\LocalNoteReplied;
use App\Events\LocalNoteShared;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\Follow;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\RemoteActor;
use App\Services\ActivityPub\Context;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use phpseclib3\Crypt\RSA;
use Tests\TestCase;

class SharedInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_activity_from_followed_actor(): void
    {
        /** @var \App\Models\ActivityPub\LocalActor $localActor */
        $localActor = LocalActor::factory()->create();

        $remoteActorKey = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
        /** @var \App\Models\ActivityPub\RemoteActor $remoteActor */
        $remoteActor = RemoteActor::factory()->withPublicKey($remoteActorKey->getPublicKey()->toString('PKCS1'))->create();
        // Local actor follows the remote one
        Follow::factory()
            ->accepted()
            ->for($remoteActor, 'target')
            ->for($localActor, 'actor')
            ->create();

        $activity = $this->generateCreateActivity($remoteActor);

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json',
        ];

        $url = route('shared-inbox');
        $data = $activity->toArray();

        $headers = $this->sign($remoteActorKey, $remoteActor->publicKeyId, $url, json_encode($data), $headers);

        Event::fake([
            LocalNoteReplied::class,
            LocalActorMentioned::class,
        ]);
        Http::fake([
            $remoteActor->inbox => Http::response('', Response::HTTP_ACCEPTED),
        ]);
        $response = $this->postJson($url, $data, $headers);
        $response->assertAccepted();

        $this->assertCount(1, $remoteActor->notes);
        $this->assertDatabaseHas('activities', [
            'type' => 'Create',
            'activityId' => $activity->id,
            'actor_id' => $remoteActor->id,
        ]);

        Http::assertSent(function (Request $request) use ($remoteActor) {
            return $request->url() === $remoteActor->inbox;
        });

        Event::assertNotDispatched(LocalActorMentioned::class);
        Event::assertNotDispatched(LocalNoteReplied::class);
    }

    public function test_create_activity_mention_from_followed_actor(): void
    {
        /** @var \App\Models\ActivityPub\LocalActor $localActor */
        $localActor = LocalActor::factory()->create();

        $remoteActorKey = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
        /** @var \App\Models\ActivityPub\RemoteActor $remoteActor */
        $remoteActor = RemoteActor::factory()->withPublicKey($remoteActorKey->getPublicKey()->toString('PKCS1'))->create();
        // Local actor follows the remote one
        Follow::factory()
            ->accepted()
            ->for($remoteActor, 'target')
            ->for($localActor, 'actor')
            ->create();

        $activity = $this->generateCreateActivity($remoteActor);
        $activity->cc = array_merge([$localActor->activityId], $activity->cc);
        $activity->object->content .= ' cc ' . $localActor->canonical_username;
        $activity->object->tag = [[
            'type' => 'Mention',
            'href' => $localActor->activityId,
            'name' => $localActor->canonical_username,
        ]];
        $activity->object->cc = array_merge([$localActor->activityId], $activity->object->cc);

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json',
        ];

        $url = route('shared-inbox');
        $data = $activity->toArray();

        $headers = $this->sign($remoteActorKey, $remoteActor->publicKeyId, $url, json_encode($data), $headers);

        Event::fake([
            LocalNoteReplied::class,
            LocalActorMentioned::class,
        ]);
        Http::fake([
            $remoteActor->inbox => Http::response('', Response::HTTP_ACCEPTED),
        ]);
        $response = $this->postJson($url, $data, $headers);
        $response->assertAccepted();

        $this->assertCount(1, $remoteActor->notes);
        $this->assertCount(1, $localActor->mentions);
        $this->assertDatabaseHas('activities', [
            'type' => 'Create',
            'activityId' => $activity->id,
            'actor_id' => $remoteActor->id,
        ]);

        Http::assertSent(function (Request $request) use ($remoteActor) {
            return $request->url() === $remoteActor->inbox;
        });

        Event::assertDispatched(LocalActorMentioned::class);
        Event::assertNotDispatched(LocalNoteReplied::class);
    }

    public function test_create_activity_mention_from_non_followed_actor(): void
    {
        /** @var \App\Models\ActivityPub\LocalActor $localActor */
        $localActor = LocalActor::factory()->create();

        $remoteActorKey = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
        /** @var \App\Models\ActivityPub\RemoteActor $remoteActor */
        $remoteActor = RemoteActor::factory()->withPublicKey($remoteActorKey->getPublicKey()->toString('PKCS1'))->create();

        $activity = $this->generateCreateActivity($remoteActor);
        $activity->object->content .= ' cc ' . $localActor->canonical_username;
        $activity->object->tag = [[
            'type' => 'Mention',
            'href' => $localActor->activityId,
            'name' => $localActor->canonical_username,
        ]];

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json',
        ];

        $url = route('shared-inbox');
        $data = $activity->toArray();

        $headers = $this->sign($remoteActorKey, $remoteActor->publicKeyId, $url, json_encode($data), $headers);

        Event::fake([
            LocalNoteReplied::class,
            LocalActorMentioned::class,
        ]);
        Http::fake([
            $remoteActor->inbox => Http::response('', Response::HTTP_ACCEPTED),
        ]);
        $response = $this->postJson($url, $data, $headers);
        $response->assertAccepted();

        $this->assertCount(1, $remoteActor->notes);
        $this->assertCount(1, $localActor->mentions);
        $this->assertDatabaseHas('activities', [
            'type' => 'Create',
            'activityId' => $activity->id,
            'actor_id' => $remoteActor->id,
        ]);

        Http::assertNotSent(function (Request $request) use ($remoteActor) {
            return $request->url() === $remoteActor->inbox;
        });

        Event::assertDispatched(LocalActorMentioned::class);
        Event::assertNotDispatched(LocalNoteReplied::class);
    }

    public function test_share_note(): void
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

        $url = route('shared-inbox');
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
