<?php

declare(strict_types=1);

namespace App\Processes;

use App\Jobs\Application\Posts\AddAttachments;
use App\Jobs\Application\Posts\CreateNewPost;
use App\Jobs\Application\Posts\ParseHashtags;
use App\Jobs\Application\Posts\ParseLinks;
use App\Jobs\Application\Posts\PublishPost as PostsPublishPost;
use App\Jobs\Application\Posts\SchedulePost;

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
}
