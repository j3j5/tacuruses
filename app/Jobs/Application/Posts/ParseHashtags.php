<?php

namespace App\Jobs\Application\Posts;

use App\Domain\Application\Note;
use Closure;
use Illuminate\Support\Facades\Log;
use Safe\Exceptions\PcreException;

use function Safe\preg_match_all;
use function Safe\preg_replace;

final class ParseHashtags
{
    private const REGEX = '/(?:^|\s|>)?(?<hashtag>#\w+)(?:$|\s|<)/u';
    private const REPLACE_REGEX = '/(^|\s|>)?#(\w+)($|\s|<)/u';

    /**
     * Execute the job.
     */
    public function handle(Note $noteDto, Closure $next) : Note
    {
        $model = $noteDto->getModel();
        $tags = [];

        foreach ($model->contentMap as $lang => $content) {
            $hashtags = $this->getHashtags($content);
            if (count($hashtags) > 0) {
                foreach ($hashtags as $hashtag) {
                    // Add to tags
                    if (!in_array($hashtag, $tags)) {
                        $tag = [
                            'type' => 'Hashtag',
                            'href' => route('tag.show', [mb_substr($hashtag, 1)]),
                            'name' => $hashtag,
                        ];
                        $tags[] = $tag;
                    }

                    $replacement = str_replace('tagName', '${2}', '${1}<a class="" href="' . route('tag.show', ['tagName']) . '">#${2}</a>${3}');
                }
                $model->contentMap = array_merge($model->contentMap, [$lang => preg_replace(self::REPLACE_REGEX, $replacement, $content)]);
            }
        }

        $model->tags = array_merge($model->tags, $tags);
        $model->save();

        $noteDto->setModel($model);

        return $next($noteDto);
    }

    /**
     * @return array<int, string>
     */
    private function getHashtags(string $content) :array
    {
        $hashtags = [];
        try {
            preg_match_all(self::REGEX, $content, $matches);
        } catch (PcreException $e) {
            Log::notice($e->getMessage());
            return [];
        }
        if ($matches) {
            $hashtagCount = array_count_values($matches[1]);
            $hashtags = array_keys($hashtagCount);
        }
        return $hashtags;
    }
}
