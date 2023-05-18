<?php

namespace App\Jobs\Application\Posts;

use App\Domain\Application\Note;
use Closure;

final class PublishPost
{

    /**
     * Execute the job.
     */
    public function handle(Note $noteDto, Closure $next) : mixed
    {
        if (!(bool) $noteDto->draft) {
            $noteDto->getModel()->publish();
        }

        return $next($noteDto);
    }
}
