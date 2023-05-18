<?php

namespace App\Jobs\Application\Posts;

use App\Domain\Application\Note;
use Closure;

final class PublishPost
{

    /**
     * Execute the job.
     */
    public function handle(Note $noteDto, Closure $next)
    {
        if (!(bool) $noteDto->draft) {
            $noteDto->getModel()->publish();
        }

        return $next($noteDto);
    }
}
