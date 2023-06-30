<?php

namespace App\Http\Resources\ActivityPub;

use ActivityPhp\Type\Core\Collection;
use ActivityPhp\Type\Core\CollectionPage;
use App\Domain\ActivityPub\Mastodon\Note as MastodonNote;
use App\Models\ActivityPub\Note;
use App\Services\ActivityPub\Context;
use App\Traits\Resources\ActivityPubResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ActivityPub\LocalNote
 */
class RepliesResource extends JsonResource
{
    use ActivityPubResource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request) : array
    {
        $context = [
            Context::ACTIVITY_STREAMS,
            Context::$status,
        ];

        $collection = new Collection();
        $collection->id = route('note.replies', [$this->actor, $this]);
        $collection->set('@context', $context);

        $page = new CollectionPage();
        $page->id = route('note.replies', [$this->actor, $this, 'page' => 1]);
        $page->next = route('note.replies', [$this->actor, $this, 'page' => 1]);
        $page->partOf = route('note.replies', [$this->actor, $this]);
        $page->items = $this->directReplies->map(fn (Note $note) : MastodonNote => $note->getAPNote())->toArray();

        $collection->first = $page;

        return $collection->toArray();
    }
}
