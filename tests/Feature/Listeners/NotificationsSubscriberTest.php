<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use ActivityPhp\Type;
use App\Enums\ActivityTypes;
use App\Enums\NotificationTypes;
use App\Events\LocalActorFollowed;
use App\Events\LocalActorMentioned;
use App\Events\LocalNoteLiked;
use App\Events\LocalNoteReplied;
use App\Events\LocalNoteShared;
use App\Listeners\NotificationsSubscriber;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\RemoteActor;
use App\Services\ActivityPub\Context;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use phpseclib3\Crypt\RSA;
use Tests\TestCase;

class NotificationsSubscriberTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_attached_to_event(): void
    {
        Event::fake();
        Event::assertListening(
            LocalActorFollowed::class,
            [NotificationsSubscriber::class, 'createNotificationForNewFollow']
        );

        Event::assertListening(
            LocalActorMentioned::class,
            [NotificationsSubscriber::class, 'createNotificationForMention']
        );
        Event::assertListening(
            LocalNoteLiked::class,
            [NotificationsSubscriber::class, 'createNotificationForLike']
        );
        Event::assertListening(
            LocalNoteReplied::class,
            [NotificationsSubscriber::class, 'createNotificationForReply']
        );
        Event::assertListening(
            LocalNoteShared::class,
            [NotificationsSubscriber::class, 'createNotificationForShare']
        );

    }

    public function test_new_follow_creates_notification(): void
    {
        /** @var \App\Models\ActivityPub\LocalActor $localActor */
        $localActor = LocalActor::factory()->create();
        $remoteActor = RemoteActor::factory()->create();

        $remoteActorKey = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
        /** @var \App\Models\ActivityPub\RemoteActor $remoteActor */
        $remoteActor = RemoteActor::factory()
            ->withPublicKey($remoteActorKey->getPublicKey()->toString('PKCS1'))
            ->create();

        $actorInfo = $remoteActor->getAPActor()->toArray();

        Http::fake([
            $actorInfo['id'] => Http::response($actorInfo),
            $actorInfo['publicKey']['id'] => Http::response($actorInfo),
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

        $url = route('actor.inbox', [$localActor]);
        $headers = $this->sign($remoteActorKey, $actorInfo['publicKey']['id'], $url, json_encode($data), $headers);
        $response = $this->postJson($url, $data, $headers);

        $response->assertAccepted();

        $this->assertCount(1, $localActor->notifications);
        $this->assertSame(NotificationTypes::FOLLOW, $localActor->notifications->first()->type);
        $this->assertSame($remoteActor->name, Arr::get($localActor->notifications->first()->data, 'replace.user'));
        $this->assertSame($remoteActor->canonical_username, Arr::get($localActor->notifications->first()->data, 'replace.username'));
        $this->assertSame($remoteActor->domain, Arr::get($localActor->notifications->first()->data, 'replace.instance'));

    }

    public function test_reply_events_create_notification(): void
    {
        /** @var \App\Models\ActivityPub\LocalActor $localActor */
        $localActor = LocalActor::factory()->create();
        $remoteActor = RemoteActor::factory()->create();

        $remoteActorKey = RSA::createKey()->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
        /** @var \App\Models\ActivityPub\RemoteActor $remoteActor */
        $remoteActor = RemoteActor::factory()
            ->withPublicKey($remoteActorKey->getPublicKey()->toString('PKCS1'))
            ->create();

        /** @var \App\Models\ActivityPub\LocalNote $note */
        $note = LocalNote::factory()->public()->for($localActor, 'actor')->create();

        $actorInfo = $remoteActor->getAPActor()->toArray();

        Http::fake([
            $actorInfo['id'] => Http::response($actorInfo),
            $actorInfo['publicKey']['id'] => Http::response($actorInfo),
            $actorInfo['inbox'] => Http::response('', 202),
        ]);

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json',
        ];

        $activity = $this->generateCreateActivity($remoteActor);
        $activity->cc = array_merge($activity->cc, [$localActor->activityId]);
        $activity->object->inReplyTo = $note->activityId;
        $activity->object->content = $localActor->canonical_username . ' ' . $activity->object->content;
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

        Http::fake([
            $remoteActor->inbox => Http::response('', Response::HTTP_ACCEPTED),
        ]);
        $response = $this->postJson($url, $data, $headers);
        $response->assertAccepted();

        $this->assertCount(1, $localActor->notifications);
        $this->assertSame(NotificationTypes::REPLY, $localActor->notifications->first()->type);
        $this->assertSame($remoteActor->name, Arr::get($localActor->notifications->first()->data, 'replace.user'));
        $this->assertSame($remoteActor->canonical_username, Arr::get($localActor->notifications->first()->data, 'replace.username'));
        $this->assertSame($remoteActor->domain, Arr::get($localActor->notifications->first()->data, 'replace.instance'));

    }
}
