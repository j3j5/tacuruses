<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\Note;
use Webmozart\Assert\Assert;

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
        Assert::isInstanceOf($noteReplied, LocalNote::class);
        Assert::eq($note->replyingTo->id, $noteReplied->id, 'Note ' . $note->id . ' does not seem to be replying to ' . $noteReplied->id);

        $this->note = $note;
        $this->noteReplied = $noteReplied;
    }
}
