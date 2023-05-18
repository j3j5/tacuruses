<?php

namespace App\Http\Controllers\API\Mastodon;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Mastodon\PostMediaRequest;
use App\Http\Resources\API\Mastodon\MediaResource;
use App\Processes\MediaAttachmentUpload;

class PostMedia extends Controller
{

    public function __construct(
        private readonly MediaAttachmentUpload $process,
    ) {
    }

    public function __invoke(PostMediaRequest $request) : MediaResource
    {
        $media = $this->process->run($request->getDTO());

        return new MediaResource($media->getModel());
    }
}
