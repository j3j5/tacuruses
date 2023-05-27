<?php

namespace App\Jobs\Application\Posts;

use App\Domain\Application\Note;
use Closure;

final class ParseLinks
{
    private const REGEX = '%(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@|\d{1,3}(?:\.\d{1,3}){3}|(?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))(?::\d+)?(?:[^\s]*)?%iu';

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Note $noteDto, Closure $next) : Note
    {
        $model = $noteDto->getModel();

        $contentMap = $model->contentMap;
        foreach ($contentMap as $lang => $content) {
            if (strip_tags($content) !== $content) {
                // Content is HTML, ignore and let them handle their own links
                continue;
            }

            $replacement = '<a class="" href="${0}" target="_blank" rel="noreferer noopener">${0}</a>';
            $contentMap[$lang] = preg_replace(self::REGEX, $replacement, $content);

        }
        $model->contentMap = $contentMap;
        $model->save();

        $noteDto->setModel($model);

        return $next($noteDto);
    }
}
