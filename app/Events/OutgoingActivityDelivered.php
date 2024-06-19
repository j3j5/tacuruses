<?php

declare(strict_types=1);

namespace App\Events;

use ActivityPhp\Type\Core\Activity;
use App\Models\ActivityPub\LocalActor;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OutgoingActivityDelivered
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly LocalActor $actor,
        public readonly Activity $activity,
        public readonly string $inbox,
    ) {
        //
    }
}
