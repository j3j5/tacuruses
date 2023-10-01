<?php

declare(strict_types=1);

namespace App\Processes;

use App\Http\Requests\API\Mastodon\UpdateStatusRequest;
use App\Jobs\Application\Posts\AddAttachments;
use App\Jobs\Application\Posts\ParseHashtags;
use App\Jobs\Application\Posts\ParseLinks;
use App\Jobs\Application\Posts\ParseMentions;
use App\Jobs\Application\Posts\ParseNewLines;
use App\Jobs\Application\Posts\PublishPost;
use App\Jobs\Application\Posts\SchedulePost;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class UpdatePost extends Process
{
    protected array $tasks = [
        UpdatePost::class,
        AddAttachments::class,
        // ParseNewLines::class,
        // ParseLinks::class,
        // ParseHashtags::class,
        // ParseMentions::class,
        // ParseEmojis::class,  // For custom emojis
        // SchedulePost::class,
        PublishPost::class,
    ];

    /**
     *
     * @param \App\Http\Requests\API\Mastodon\UpdateStatusRequest $request
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \App\Http\Requests\API\Mastodon\UpdateStatusRequest
     */
    public function run(object $request) : mixed
    {
        if (!$request instanceof UpdateStatusRequest) {
            throw new RuntimeException('Invalid param for UpdatePost process. An UpdateStatusRequest was expected');
        }

        $attempts = 3;
        DB::transaction(function () use ($request) {
            $request = parent::run($request);
        }, $attempts);
        DB::commit();

        return $request;
    }
}