<?php

declare(strict_types=1);

namespace App\Processes;

use App\Domain\Application\Note;
use App\Jobs\Application\Posts\AddAttachments;
use App\Jobs\Application\Posts\CreateNewPost;
use App\Jobs\Application\Posts\ParseHashtags;
use App\Jobs\Application\Posts\ParseLinks;
use App\Jobs\Application\Posts\ParseMentions;
use App\Jobs\Application\Posts\ParseNewLines;
use App\Jobs\Application\Posts\PublishPost as PostsPublishPost;
use App\Jobs\Application\Posts\SchedulePost;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PublishPost extends Process
{
    protected array $tasks = [
        CreateNewPost::class,
        AddAttachments::class,
        ParseNewLines::class,
        ParseLinks::class,
        ParseHashtags::class,
        ParseMentions::class,
        // ParseEmojis::class,  // For custom emojis
        SchedulePost::class,
        PostsPublishPost::class,
    ];

    /**
     *
     * @param \App\Domain\Application\Note $note
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \App\Domain\Application\Note
     */
    public function run(object $note) : mixed
    {
        if (!$note instanceof Note) {
            throw new RuntimeException('Invalid param for PublishPost process. An Application\\Note was expected');
        }

        $attempts = 3;
        DB::transaction(function () use ($note) {
            $note = parent::run($note);
        }, $attempts);
        DB::commit();
        return $note;
    }
}
