<?php

namespace App\Http\Controllers\API\Mastodon;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Mastodon\PostStatusRequest;
use App\Http\Resources\API\Mastodon\StatusResource;
use App\Processes\PublishPost;

class PostStatus extends Controller
{
    public function __construct(
        private readonly PublishPost $process,
    ) {
    }

    public function __invoke(PostStatusRequest $request) : StatusResource
    {
        /** @var \App\Domain\Application\Note $note */
        $note = $this->process->run($request->getDTO());
        return new StatusResource($note->getModel());
    }
}
