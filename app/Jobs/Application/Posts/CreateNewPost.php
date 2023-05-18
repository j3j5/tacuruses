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
    public function handle(Note $noteDto, Closure $next)
    {
        $note = new LocalNote(['type' => 'Note']);
        $note->actor_id = $noteDto->getActor()->id;
        $note->setRelation('actor', $noteDto->getActor());

        $note->content = $noteDto->get('content', '');
        if (empty($note->content)) {
            $note->content = $noteDto->get('status', '');
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
