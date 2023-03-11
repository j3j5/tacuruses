<?php

namespace App\Http\Resources;

use ActivityPhp\Type\Core\Collection;
use ActivityPhp\Type\Core\CollectionPage;
use App\Services\ActivityPub\Context;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ActivityPub\LocalNote
 */
class RepliesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $context = ['@context' => [
            Context::ACTIVITY_STREAMS,
            [
                'ostatus' => 'http://ostatus.org#',
                'atomUri' => 'ostatus:atomUri',
                'inReplyToAtomUri' => 'ostatus:inReplyToAtomUri',
                'conversation' => 'ostatus:conversation',
                'sensitive' => 'as:sensitive',
                'toot' => 'http://joinmastodon.org/ns#',
                'votersCount' => 'toot:votersCount',
                'Hashtag' => 'as:Hashtag',
            ],
        ]];

        $collection = new Collection();
        $collection->id = route('note.replies', [$this->actor, $this]);

        $page = new CollectionPage();
        $page->id = route('note.replies', [$this->actor, $this, 'page' => 1]);
        $page->next = route('note.replies', [$this->actor, $this, 'page' => 1]);
        $page->partOf = route('note.replies', [$this->actor, $this]);
        $page->items = $this->replies->transform->getAP;

        $collection->first = $page;
    }
}