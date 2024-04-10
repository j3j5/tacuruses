<?php

namespace App\Listeners;

use App\Enums\NotificationTypes;
use App\Events\LocalActorMentioned;
use App\Events\LocalNoteLiked;
use App\Events\LocalNoteReplied;
use App\Events\LocalNoteShared;
use App\Events\NewNotification;
use App\Models\Notification;
use App\Notifications\NewLike;
use App\Notifications\NewMention;
use App\Notifications\NewReply;
use App\Notifications\NewShare;
use Illuminate\Events\Dispatcher;

class NotificationsSubscriber
{

    public function createNotificationForMention(LocalActorMentioned $event) : void
    {
        $event->actor->notify(new NewMention(
            actor: $event->note->actor,
            activity: $event->note->activity,
        ));
    }

    public function createNotificationForLike(LocalNoteLiked $event) : void
    {
        $event->like->target->actor->notify(new NewLike(
            actor: $event->like->actor,
            activity: $event->like,
        ));
    }

    public function createNotificationForReply(LocalNoteReplied $event) : void
    {
        $event->note->replyingTo->actor->notify(new NewReply(
            actor: $event->note->actor,
            activity: $event->note->activity,
        ));
    }

    public function createNotificationForShare(LocalNoteShared $event) : void
    {
        $event->share->target->actor->notify(
            new NewShare(
                actor: $event->share->actor,
                activity: $event->share,
            )
        );
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe(Dispatcher $events) : array
    {
        return [
            LocalActorMentioned::class => 'createNotificationForMention',
            LocalNoteLiked::class => 'createNotificationForLike',
            LocalNoteReplied::class => 'createNotificationForReply',
            LocalNoteShared::class => 'createNotificationForShare',
        ];
    }
}
