<?php

namespace Tests\Unit\Listeners;

use App\Events\LocalActorMentioned;
use App\Events\LocalNoteLiked;
use App\Events\LocalNoteReplied;
use App\Events\LocalNoteShared;
use App\Listeners\NotificationsSubscriber;
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

    public function test_reply_events_create_notification()
    {
        $this->markTestIncomplete('todo');
    }
}
