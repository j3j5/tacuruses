<?php

namespace Tests\Feature\Http\Controllers\ActivityPub;

use ActivityPhp\Type;
use App\Models\ActivityPub\RemoteActor;
use App\Services\ActivityPub\Context;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeleteActivityTest extends TestCase
{
    use LazilyRefreshDatabase, WithFaker;

    public function test_mastodon_delete_activity_for_non_existent_user()
    {
        $remoteActor = RemoteActor::factory()->withPublicKey('abc')->make();

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
        $response = $this->withHeaders([
            'Date' => now()->toIso8601ZuluString(),
            'Signature' => Str::random(345),
        ])->postJson($url, $activity->toArray());

        $response->assertOk();
    }

    public function test_mastodon_delete_activity_for_existent_user()
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
        $response = $this->withHeaders([
            'Date' => now()->toIso8601ZuluString(),
            'Signature' => Str::random(345),
        ])->postJson($url, $activity->toArray());

        $response->assertOk();
        $this->assertDatabaseMissing('actors', ['id' => $remoteActor->id]);
    }

    public function test_mastodon_fake_delete_activity_for_existent_user()
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
        $response = $this->withHeaders([
            'Date' => now()->toIso8601ZuluString(),
            'Signature' => Str::random(345),
        ])->postJson($url, $activity->toArray());

        $response->assertOk();
        $this->assertDatabaseHas('actors', ['id' => $remoteActor->id]);
    }

}
