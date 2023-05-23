<?php

namespace App\Jobs\Application\Posts;

use App\Domain\Application\Note;
use App\Enums\Visibility;
use App\Models\ActivityPub\LocalNote;
use Closure;

final class CreateNewPost
{

    /**
     * Execute the job.
     */
    public function handle(Note $noteDto, Closure $next) : mixed
    {
        $note = new LocalNote(['type' => 'Note']);
        $actor = $noteDto->getActor();
        $note->actor_id = $actor->id;
        $note->setRelation('actor', $noteDto->getActor());

        $content = $noteDto->get('content', $noteDto->get('status', ''));
        if ($content !== '') {
            $note->contentMap = [
                $actor->language => $content,
            ];
        }

        $note->replyTo_id = $noteDto->get('replyTo_id');
        if (empty($note->replyTo_id)) {
            $note->replyTo_id = $noteDto->get('in_reply_to_id');
        }

        $note->sensitive = $noteDto->get('sensitive', false);
        $note->summary = $noteDto->get('spoiler_text');
        $note->visibility = $noteDto->get('visibility', Visibility::PRIVATE);
        $note->fillRecipients();
        // $note->language = $noteDto->getActor()->language;

        $note->save();

        $noteDto->setModel($note);

        return $next($noteDto);
    }
}
