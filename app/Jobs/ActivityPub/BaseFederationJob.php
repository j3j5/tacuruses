<?php

namespace App\Jobs\ActivityPub;

use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class BaseFederationJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted (unlimited).
     *
     * @var int
     */
    public $tries = 0;

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): DateTime
    {
        return now()->addWeek();
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [
            10 * 60,        // 10 minutes
            60 * 60,        // 1 hour
            6 * 60 * 60,    // 6 hours
            12 * 60 * 60,   // 24 hours
            24 * 60 * 60,   // 24 hours
        ];
    }
}
