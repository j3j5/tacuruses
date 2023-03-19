<?php

namespace App\Listeners;

use App\Events\LocalNotePublished;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\Follow;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNoteToFollowers implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(LocalNotePublished $event) : void
    {
        $followers = $event->note->actor->followers
            ->map(fn (Follow $follow) => $follow->actor)
            ->filter(
                fn (Actor $follower) : bool => $follower instanceof RemoteActor
            );
        $followers->filter(fn (RemoteActor $actor) : bool => !empty($actor->sharedInbox))
            ->unique('sharedInbox')
            ->each->sendNote($event->note);

        $followers->filter(fn (RemoteActor $actor) : bool => empty($actor->sharedInbox))
            ->unique('inbox')
            ->each->sendNote($event->note);
    }
}
