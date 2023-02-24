<?php

namespace App\Http\Resources;

use App\Services\ActivityPub\Context;
use App\Traits\Resources\ActivityPubResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 *
 * @mixin \App\Domain\ActivityPub\Contracts\Note
 */
class NoteResource extends JsonResource
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
        $context = [
            '@context' => [
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
                    "Emoji" => "toot:Emoji",
                    // 'blurhash' => 'toot:blurhash',
                    'focalPoint' => [
                        '@container' => '@list',
                        '@id' => 'toot:focalPoint',
                    ],
                ],
            ],
        ];

        $actor = $this->getActor();
        $data = [
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

            // "atomUri" => "https://mastodon.uy/users/j3j5/statuses/109316859449385938",
            // "conversation": "tag:hachyderm.io,2022-11-10:objectId=1050302:objectType=Conversation",
            'inReplyToAtomUri' => null,
            'content' => $this->getText(),
            'contentMap' => [
                $this->getLanguage() => $this->getText(),
            ],
            'attachment' => $this->getAttachment(),
            'tag' => $this->getTags(),
            'replies' => $this->getReplies(),
        ];

        $data = array_merge($context, $data);
        return $data;
    }
}
