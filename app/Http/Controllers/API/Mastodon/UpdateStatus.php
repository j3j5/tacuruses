<?php

namespace App\Http\Controllers\API\Mastodon;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Mastodon\UpdateStatusRequest;
use App\Http\Resources\API\Mastodon\StatusResource;
use App\Models\ActivityPub\LocalNote;
use App\Processes\UpdatePost;
use Illuminate\Http\Request;

class UpdateStatus extends Controller
{
    public function __construct(
        private readonly UpdatePost $process,
    ) {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateStatusRequest $request, LocalNote $note) : StatusResource
    {
        /** @var \App\Domain\Application\Note $note */
        $note = $this->process->run($request);
        return new StatusResource($note->getModel());
    }
}
