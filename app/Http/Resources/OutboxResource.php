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
            'actor' => $this->actor->getProfileUrl(),
            'published' => $this->created_at->toIso8601ZuluString(),
            'to' => [
                Context::ACTIVITY_STREAMS_PUBLIC,
            ],
            'cc' => [
                $this->actor->getFollowersUrl(),
            ],
            'object' => [
                'id' => $this->activityId,
                'type' => 'Note',
                'summary' => null,
                'inReplyTo' => null,
                'published' => $this->created_at->toIso8601ZuluString(),
                'url' => $this->url,
                'attributedTo' => $this->actor->getProfileUrl(),
                'to' => [
                    Context::ACTIVITY_STREAMS_PUBLIC,
                ],
                'cc' => [
                    $this->actor->getFollowersUrl(),
                ],
                'sensitive' => $this->isSensitive(),

            ],
            'inReplyToAtomUri' => null,
            // "conversation": "tag:hachyderm.io,2022-11-10:objectId=1050302:objectType=Conversation",
            'content' => $this->text,
            'contentMap' => [
                $this->language => $this->text,
            ],
            'attachment' => $this->attachments,
            'tag' => $this->tags,
        ];
    }
}
