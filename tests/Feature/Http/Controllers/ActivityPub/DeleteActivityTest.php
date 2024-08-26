<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ActivityPub;

use ActivityPhp\Type;
use App\Http\Middleware\ActivityPub\VerifyHttpSignature;
use App\Models\ActivityPub\RemoteActor;
use App\Models\ActivityPub\RemoteNote;
use App\Services\ActivityPub\Context;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use phpseclib3\Crypt\RSA;
use Tests\TestCase;

class DeleteActivityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_mastodon_delete_actor_activity_for_non_existent_user(): void
    {
        $remoteActorKey = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
        /** @var \App\Models\ActivityPub\RemoteActor $remoteActor */
        $remoteActor = RemoteActor::factory()
            ->withPublicKey($remoteActorKey->getPublicKey()->toString('PKCS1'))
            ->create();

        Http::fake([
            $remoteActor->activityId => Http::response('', 410),
        ]);

        $activity = Type::create('Delete', [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $remoteActor->activityId . '#delete',
            'actor' => $remoteActor->activityId,
            'to' => [
              Context::ACTIVITY_STREAMS_PUBLIC,
            ],
            'object' => $remoteActor->activityId,
            'signature' => [
              'type' => 'RsaSignature2017',
              'creator' => $remoteActor->publicKeyId,
              'created' => now()->toIso8601ZuluString(),
              'signatureValue' => Str::random(345),
            ],
        ]);

        $url = route('shared-inbox');
        $response = $this->postJson($url, $activity->toArray(), [
            'Date' => now()->toIso8601ZuluString(),
            'Signature' => Str::random(345),
            'CONTENT_TYPE' => 'application/activity+json',
        ]);

        $response->assertOk();
    }

    public function test_mastodon_delete_actor_activity_for_existent_user(): void
    {
        $remoteActor = RemoteActor::factory()->withPublicKey('abc')->create();

        Http::fake([
            $remoteActor->activityId => Http::response('', 410),
        ]);

        $activity = Type::create('Delete', [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $remoteActor->activityId . '#delete',
            'actor' => $remoteActor->activityId,
            'to' => [
              Context::ACTIVITY_STREAMS_PUBLIC,
            ],
            'object' => $remoteActor->activityId,
            'signature' => [
              'type' => 'RsaSignature2017',
              'creator' => $remoteActor->publicKeyId,
              'created' => now()->toIso8601ZuluString(),
              'signatureValue' => Str::random(345),
            ],
        ]);

        $url = route('shared-inbox');
        $response = $this->postJson($url, $activity->toArray(), [
            'Date' => now()->toIso8601ZuluString(),
            'Signature' => Str::random(345),
            'CONTENT_TYPE' => 'application/activity+json',
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('actors', ['id' => $remoteActor->id]);
    }

    public function test_mastodon_fake_delete_activity_for_existent_user(): void
    {
        $remoteActor = RemoteActor::factory()->withPublicKey('abc')->create();

        Http::fake([
            $remoteActor->activityId => Http::response('', 200),
        ]);

        $activity = Type::create('Delete', [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $remoteActor->activityId . '#delete',
            'actor' => $remoteActor->activityId,
            'to' => [
              Context::ACTIVITY_STREAMS_PUBLIC,
            ],
            'object' => $remoteActor->activityId,
            'signature' => [
              'type' => 'RsaSignature2017',
              'creator' => $remoteActor->publicKeyId,
              'created' => now()->toIso8601ZuluString(),
              'signatureValue' => Str::random(345),
            ],
        ]);

        $url = route('shared-inbox');
        $response = $this->postJson($url, $activity->toArray(), [
            'Date' => now()->toIso8601ZuluString(),
            'Signature' => Str::random(345),
            'CONTENT_TYPE' => 'application/activity+json',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('actors', ['id' => $remoteActor->id]);
    }

    public function test_mastodon_delete_remote_status_for_deleted_status(): void
    {
        $notes = RemoteNote::factory()->public()->count(3)
            ->for(RemoteActor::factory()->withPublicKey('abc'), 'actor')
            ->create();
        $noteToDelete = $notes->random();

        Http::fake([
            $noteToDelete->activityId => Http::response('', 410),
        ]);

        $activity = Type::create('Delete', [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $noteToDelete->activityId . '#delete',
            'actor' => $noteToDelete->actor->activityId,
            'to' => [
              Context::ACTIVITY_STREAMS_PUBLIC,
            ],
            'object' => Type::create('Tombstone', [
                'id' => $noteToDelete->activityId,
            ]),
            'signature' => [
              'type' => 'RsaSignature2017',
              'creator' => $noteToDelete->actor->publicKeyId,
              'created' => now()->toIso8601ZuluString(),
              'signatureValue' => Str::random(345),
            ],
        ]);

        $this->withoutMiddleware(VerifyHttpSignature::class);

        $url = route('shared-inbox');
        $response = $this->postJson($url, $activity->toArray(), [
            'Date' => now()->toIso8601ZuluString(),
            'Signature' => Str::random(345),
            'CONTENT_TYPE' => 'application/activity+json',
        ]);

        $response->assertAccepted();
        $this->assertSoftDeleted('notes', ['id' => $noteToDelete->id]);
    }

    /**
     * Ignore delete calls for notes that clearly exists
     *
     * @return void
     */
    public function test_mastodon_delete_remote_status_for_existing_status(): void
    {
        $notes = RemoteNote::factory()->public()->count(3)
            ->for(RemoteActor::factory()->withPublicKey('abc'), 'actor')
            ->create();
        $noteToDelete = $notes->random();

        Http::fake([
            $noteToDelete->activityId => Http::response('', 200),
        ]);

        $activity = Type::create('Delete', [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $noteToDelete->activityId . '#delete',
            'actor' => $noteToDelete->actor->activityId,
            'to' => [
              Context::ACTIVITY_STREAMS_PUBLIC,
            ],
            'object' => Type::create('Tombstone', [
                'id' => $noteToDelete->activityId,
            ]),
            'signature' => [
              'type' => 'RsaSignature2017',
              'creator' => $noteToDelete->actor->publicKeyId,
              'created' => now()->toIso8601ZuluString(),
              'signatureValue' => Str::random(345),
            ],
        ]);

        $this->withoutMiddleware(VerifyHttpSignature::class);

        $url = route('shared-inbox');
        $response = $this->postJson($url, $activity->toArray(), [
            'Date' => now()->toIso8601ZuluString(),
            'Signature' => Str::random(345),
            'CONTENT_TYPE' => 'application/activity+json',
        ]);

        $response->assertAccepted();

        $this->assertNotSoftDeleted('notes', ['id' => $noteToDelete->id]);
    }
}
