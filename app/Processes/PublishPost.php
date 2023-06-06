<?php

declare(strict_types=1);

namespace App\Processes;

use App\Domain\Application\Note;
use App\Jobs\Application\Posts\AddAttachments;
use App\Jobs\Application\Posts\CreateNewPost;
use App\Jobs\Application\Posts\ParseHashtags;
use App\Jobs\Application\Posts\ParseLinks;
use App\Jobs\Application\Posts\PublishPost as PostsPublishPost;
use App\Jobs\Application\Posts\SchedulePost;
use RuntimeException;

final class PublishPost extends Process
{
    protected array $tasks = [
        CreateNewPost::class,
        AddAttachments::class,
        ParseLinks::class,
        // ParseMentions
        ParseHashtags::class,
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
        return parent::run($note);
    }
}
