<?php

declare(strict_types=1);

namespace App\Jobs\Application\Posts;

use App\Domain\Application\Note;
use Closure;
use Twitter\Text\Autolink;
use Twitter\Text\Extractor;

final class ParseMentions
{
    /**
     * Constructor
     * @param \Twitter\Text\Autolink $linker
     * @return void
     */
    public function __construct(private Autolink $linker, private Extractor $extractor)
    {
        $this->linker->setExternal(false);
        $this->linker->setNoFollow(false);

        $this->linker->setUsernameClass($this->linker->getUsernameClass() . ' username');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Note $noteDto, Closure $next) : Note
    {
        // $model = $noteDto->getModel();

        // $contentMap = $model->contentMap;
        // foreach ($contentMap as $lang => $content) {
        //     // Twitter-text only handles @username but it can't handle @username@server.tld
        //     $usernames = $this->linker->autoLinkUsernamesAndLists($content);
        // }
        // $model->contentMap = $contentMap;
        // $model->save();

        // $noteDto->setModel($model);

        return $next($noteDto);
    }
}
