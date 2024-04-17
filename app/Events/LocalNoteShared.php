<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ActivityPub\ActivityAnnounce;

final class LocalNoteShared extends BaseEvent
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public readonly ActivityAnnounce $share)
    {
        //
    }

}
