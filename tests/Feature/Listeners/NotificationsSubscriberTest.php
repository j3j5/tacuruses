<?php

namespace Tests\Feature\Listeners;

use App\Events\LocalActorFollowed;
use App\Events\LocalActorMentioned;
use App\Events\LocalNoteLiked;
use App\Events\LocalNoteReplied;
use App\Events\LocalNoteShared;
use App\Listeners\NotificationsSubscriber;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationsSubscriberTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_is_attached_to_event()
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

    public function test_new_follow_creates_notification()
    {
        $this->markTestIncomplete('todo');
        /** @var \App\Models\ActivityPub\LocalActor $localActor */
        $localActor = LocalActor::factory()->create();
        $remoteActor = RemoteActor::factory()->create();

        // Event::fake();
    }

    public function test_reply_events_create_notification()
    {
        $this->markTestIncomplete('todo');
    }
}
