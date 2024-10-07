<?php

declare(strict_types=1);

namespace App\Jobs\Application\Posts;

use App\Domain\Application\Note;
use Closure;
use Illuminate\Support\Collection;

use Twitter\Text\Autolink;
use Twitter\Text\Extractor;

final class ParseHashtags
{
    public function __construct(private Autolink $linker, private Extractor $extractor)
    {
        $this->linker->setExternal(false);
        $this->linker->setNoFollow(false);

        $this->linker->setHashtagClass($this->linker->getHashtagClass() . ' hashtag');
    }

    /**
     * Execute the job.
     */
    public function handle(Note $noteDto, Closure $next) : Note
    {
        $model = $noteDto->getModel();
        $tags = new Collection();

        $contentMap = $model->contentMap;
        foreach ($contentMap as $lang => $content) {
            $entities = $this->extractor->extractHashtagsWithIndices($content);
            $contentMap[$lang] = $this->linker->autoLinkEntities($content, $entities);

            // Only get hashtags of the first language
            if ($tags->isNotEmpty()) {  /** @phpstan-ignore method.impossibleType */
                continue;
            }

            foreach ($entities as $entity) {
                if (!isset($entity['hashtag'])) {
                    continue;
                }
                $tags->push([
                    'type' => 'Hashtag',
                    'href' => route('tag.show', [$entity['hashtag']]),
                    'name' => "#{$entity['hashtag']}",
                ]);
            }
        }
        $model->contentMap = $contentMap;
        $model->tags = $tags->merge($model->tags)->unique('name')->toArray();
        $model->save();

        $noteDto->setModel($model);

        return $next($noteDto);
    }
}
