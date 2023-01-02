<?php

namespace App\Http\Resources;

use App\Services\ActivityPub\Context;
use App\Traits\Resources\ActivityPubResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 *
 * @mixin \App\Contracts\Actor
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
            'actor' => $actor->profileUrl,
            'published' => $this->getPublishedStatusAt()->toIso8601ZuluString(),
            'to' => [
                Context::ACTIVITY_STREAMS_PUBLIC,
            ],
            'cc' => [
                $actor->followersUrl,
            ],
            'object' => [
                'id' => $this->getStatusUrl(),
                'type' => 'Note',
                'summary' => null,
                'inReplyTo' => null,
                'published' => $this->getPublishedStatusAt()->toIso8601ZuluString(),
                'url' => $this->getStatusUrl(),
                'attributedTo' => $actor->profileUrl,
                'to' => [
                    Context::ACTIVITY_STREAMS_PUBLIC,
                ],
                'cc' => [
                    $actor->followersUrl,
                ],
                'sensitive' => false,

            ],
            'content' => $this->getStatus(),
            'contentMap' => [
                'es' => $this->getStatus(),
            ],
            'attachment' => [],
            'tag' => [],
        ];
    }
}
