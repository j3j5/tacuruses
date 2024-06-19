<?php

declare(strict_types=1);

namespace App\Enums\Pulse;

enum RecordTypes:string
{
    /** Our instance delivering to other fedi instances */
    case DELIVERY_INSTANCE = 'deliveries_to_fedi';
    case ACTOR_DELIVERIES = 'actor_deliveries';
    case ACTOR_INTERACTIONS = 'actor_interactions';
    case NOTE_INTERACTIONS = 'note_interactions';
}
