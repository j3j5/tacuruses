<?php

declare(strict_types=1);

namespace App\Recorders;

use App\Enums\Pulse\RecordTypes;
use App\Events\OutgoingActivityDelivered;
use Illuminate\Support\Facades\Log;
use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Recorders\Concerns;

class Deliveries
{

    use Concerns\Ignores,
        Concerns\Sampling;

    /**
     * The events to listen for.
     *
     * @var array<int, class-string>
     */
    public array $listen = [
        OutgoingActivityDelivered::class,
    ];

    /**
     * Record the activity delivery.
     */
    public function record(OutgoingActivityDelivered $event): void
    {
        if ($this->shouldSample() === false) {
            return;
        }

        if ($this->shouldIgnore($event->actor->canonical_username)) {
            return;
        }

        // Record by actor
        Pulse::record(
            type: RecordTypes::ACTOR_DELIVERIES->value,
            key: (string) $event->actor->id
        )->count();

        $instance = (string) parse_url($event->inbox, PHP_URL_HOST);

        if ($instance === '') {
            Log::warning('Pulse record for delivery trying to record emtpy instance', [$event->inbox]);
        }

        if ($this->shouldIgnore($instance)) {
            return;
        }

        // Record by instance
        Pulse::record(
            type: RecordTypes::DELIVERY_INSTANCE->value,
            key: $instance
        )->count();

    }
}
