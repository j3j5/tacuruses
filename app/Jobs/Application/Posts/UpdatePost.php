<?php

declare(strict_types=1);

namespace App\Jobs\Application\Posts;

use App\Domain\Application\Note;
use Closure;

final class UpdatePost
{

    /**
     * Execute the job.
     */
    public function handle(Note $noteDto, Closure $next) : mixed
    {
        if (!(bool) $noteDto->draft && (bool) !$noteDto->scheduled_at) {
            $noteDto->getModel()->publish();
        }

        return $next($noteDto);
    }
}
