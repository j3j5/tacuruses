<?php

namespace App\Http\Resources;

use App\Services\ActivityPub\Context;
use App\Traits\Resources\ActivityPubResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 *
 * @mixin \App\Domain\ActivityPub\Contracts\Note
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
        $actor = $this->getActor();
        return [
            'id' => $this->getActivityUrl(),
            'type' => 'Create',
            'actor' => $actor->getProfileUrl(),
            'published' => $this->getPublishedStatusAt()->toIso8601ZuluString(),
            'to' => [
                Context::ACTIVITY_STREAMS_PUBLIC,
            ],
            'cc' => [
                $actor->getFollowersUrl(),
            ],
            'object' => [
                'id' => $this->getNoteUrl(),
                'type' => 'Note',
                'summary' => null,
                'inReplyTo' => null,
                'published' => $this->getPublishedStatusAt()->toIso8601ZuluString(),
                'url' => $this->getNoteUrl(),
                'attributedTo' => $actor->getProfileUrl(),
                'to' => [
                    Context::ACTIVITY_STREAMS_PUBLIC,
                ],
                'cc' => [
                    $actor->getFollowersUrl(),
                ],
                'sensitive' => $this->isSensitive(),

            ],
            'content' => $this->getText(),
            'contentMap' => [
                $this->getLanguage() => $this->getText(),
            ],
            'attachment' => $this->getAttachment(),
            'tag' => $this->getTags(),
        ];
    }
}
