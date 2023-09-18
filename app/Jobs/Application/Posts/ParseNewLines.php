<?php

declare(strict_types=1);

namespace App\Jobs\Application\Posts;

use App\Domain\Application\Note;
use Closure;

final class ParseNewLines
{

    /**
     * Execute the job.
     */
    public function handle(Note $noteDto, Closure $next) : Note
    {
        $model = $noteDto->getModel();

        $contentMap = $model->contentMap;
        foreach ($contentMap as $lang => $content) {
            if (!$noteDto->plain_text) {
                // Content is HTML, ignore and let them handle their own new lines
                continue;
            }
            $contentMap[$lang] = str_replace(PHP_EOL, '', nl2br($content, false));
        }
        $model->contentMap = $contentMap;
        $model->save();

        $noteDto->setModel($model);

        return $next($noteDto);
    }
}
