<?php

declare(strict_types=1);

namespace App\Events;

use App\Exceptions\AppException;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\Note;
use Illuminate\Broadcasting\PrivateChannel;

final class LocalNoteReplied extends BaseEvent
{
    /**
     * Represents the new note that replies to a local note
     *
     * @var Note
     */
    public readonly Note $note;

    /**
     * Represents the local note a new note is replying to
     *
     * @var Note
     */
    public readonly LocalNote $noteReplied;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Note $note, LocalNote $noteReplied)
    {
        if ($noteReplied->replyingTo->isNot($note)) {
            throw new AppException('Note ' . $noteReplied->id . ' does not seem to be replying to ' . $note->id);
        }
        $this->note = $note;
        $this->noteReplied = $noteReplied;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
