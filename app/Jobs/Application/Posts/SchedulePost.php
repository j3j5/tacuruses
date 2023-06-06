<?php

declare(strict_types=1);

namespace App\Jobs\Application\Posts;

use App\Domain\Application\Note;
use Carbon\Carbon;
use Closure;

final class SchedulePost
{

    /**
     * Execute the job.
     */
    public function handle(Note $noteDto, Closure $next) : mixed
    {
        if ($noteDto->scheduled_at) {
            $note = $noteDto->getModel();
            // Schedule the job on the queue
            dispatch(function () use ($note) {
                $note->publish();
            })->delay(
                now()->diff(Carbon::parse($noteDto->scheduled_at))
            );
        }

        return $next($noteDto);
    }
}
