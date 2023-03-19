<?php

namespace App\Http\Resources;

use App\Services\ActivityPub\Context;
use App\Traits\Resources\ActivityPubResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 *
 * @mixin \App\Models\ActivityPub\LocalNote
 */
class OutboxResource extends JsonResource
{
    use ActivityPubResource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->activityId,
            'type' => 'Create',
            'actor' => $this->actor->url,
            /* @phpstan-ignore-next-line */
            'published' => $this->published_at->toIso8601ZuluString(),
            'to' => [
                Context::ACTIVITY_STREAMS_PUBLIC,
            ],
            'cc' => [
                $this->actor->followers_url,
            ],
            'object' => [
                'id' => $this->activityId,
                'type' => 'Note',
                'summary' => null,
                'inReplyTo' => null,
                /* @phpstan-ignore-next-line */
                'published' => $this->published_at->toIso8601ZuluString(),
                'url' => $this->url,
                'attributedTo' => $this->actor->url,
                'to' => [
                    Context::ACTIVITY_STREAMS_PUBLIC,
                ],
                'cc' => [
                    $this->actor->followers_url,
                ],
                'sensitive' => $this->sensitive,

            ],
            'inReplyToAtomUri' => null,
            // "conversation": "tag:hachyderm.io,2022-11-10:objectId=1050302:objectType=Conversation",
            'content' => $this->content,
            'contentMap' => [
                $this->language => $this->content,
            ],
            'attachment' => $this->attachments,
            'tag' => $this->tags,
        ];
    }
}
