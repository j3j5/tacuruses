<?php

namespace App\Jobs\Application\Posts;

use App\Domain\Application\Note;
use App\Models\Media;
use Closure;

final class AddAttachments
{

    /**
     * Execute the job.
     */
    public function handle(Note $noteDto, Closure $next) : Note
    {
        // MEDIA!!
        if ($noteDto->media_ids) {
            Media::whereIn('id', $noteDto->get('media_ids', ''))->update(['note_id' => $noteDto->getModel()->id]);
        }
        if ($noteDto->media) {
            foreach ($noteDto->media as $media) {
                Media::create([
                    'actor_id' => $noteDto->getActor()->id,
                    'content_type' => $media['mediaType'],
                    'remote_url' => $media['url'],
                    'description' => $media['name'],
                    'note_id' => $noteDto->getModel()->id,
                ]);
            }
        }

        return $next($noteDto);
    }
}
