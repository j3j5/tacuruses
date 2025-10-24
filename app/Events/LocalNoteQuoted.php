<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ActivityPub\ActivityQuoteRequest;

final class LocalNoteQuoted extends BaseEvent
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public readonly ActivityQuoteRequest $share)
    {
        //
    }

}
