<?php

declare(strict_types=1);

namespace App\Jobs\Application\Posts;

use App\Domain\Application\Note;
use Closure;
use Twitter\Text\Autolink;

final class ParseLinks
{
    /**
     * Constructor
     * @param \Twitter\Text\Autolink $linker
     * @return void
     */
    public function __construct(private Autolink $linker)
    {
        $this->linker->setExternal(true);
        $this->linker->setNoFollow(true);
        $this->linker->setRel('noreferrer', true);
        $this->linker->setRel('noopener', true);

        $this->linker->setURLClass($this->linker->getURLClass() . ' external-url');
    }

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
            $contentMap[$lang] = $this->linker->autoLinkURLs($content);

        }
        $model->contentMap = $contentMap;
        $model->save();

        $noteDto->setModel($model);

        return $next($noteDto);
    }
}
