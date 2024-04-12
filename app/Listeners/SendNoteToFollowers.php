<?php

namespace App\Listeners;

use App\Enums\Visibility;
use App\Events\LocalNotePublished;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;

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
        if (!in_array($event->note->visibility, [Visibility::PUBLIC, Visibility::UNLISTED, Visibility::PRIVATE])) {
            throw new RuntimeException('Direct Messages not implemented yet!');
        }

        $event->note->actor->loadMissing(['followers']);

        // Get all remote actors
        $followers = $event->note->actor->followers
            ->filter(
                fn (Actor $follower) : bool => $follower instanceof RemoteActor
            );

        // Deduplicate first by shared inbox address
        $followers->filter(fn (RemoteActor $actor) : bool => !empty($actor->sharedInbox))
            ->unique('sharedInbox')
            ->each->sendNote($event->note);

        // Deduplicate now by inbox address
        $followers->filter(fn (RemoteActor $actor) : bool => empty($actor->sharedInbox))
            ->unique('inbox')
            ->each->sendNote($event->note);
    }
}
