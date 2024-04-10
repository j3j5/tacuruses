<?php

namespace Tests\Feature\Http\Controllers\ActivityPub;

use ActivityPhp\Type;
use App\Domain\ActivityPub\Mastodon\Create;
use App\Events\LocalActorMentioned;
use App\Events\LocalNoteReplied;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\Follow;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\RemoteActor;
use App\Services\ActivityPub\Context;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use phpseclib3\Crypt\RSA;
use Tests\TestCase;

class SharedInboxTest extends TestCase
{
    use LazilyRefreshDatabase;
    use WithFaker;

    public function test_create_activity_from_followed_actor()
    {
        /** @var \App\Models\ActivityPub\LocalActor $localActor */
        $localActor = LocalActor::factory()->create();

        $remoteActorKey = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
        /** @var \App\Models\ActivityPub\RemoteActor $remoteActor */
        $remoteActor = RemoteActor::factory()->withPublicKey($remoteActorKey->getPublicKey()->toString('PKCS1'))->create();
        // Local actor follows the remote one
        Follow::factory()
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

    public function test_create_activity_mention_from_followed_actor()
    {
        /** @var \App\Models\ActivityPub\LocalActor $localActor */
        $localActor = LocalActor::factory()->create();

        $remoteActorKey = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
        /** @var \App\Models\ActivityPub\RemoteActor $remoteActor */
        $remoteActor = RemoteActor::factory()->withPublicKey($remoteActorKey->getPublicKey()->toString('PKCS1'))->create();
        // Local actor follows the remote one
        Follow::factory()
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

    public function test_create_activity_mention_from_non_followed_actor()
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

    private function generateCreateActivity(Actor $actor, string $note = null) : Create
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
}
