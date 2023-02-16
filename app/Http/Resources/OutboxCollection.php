<?php

namespace App\Http\Resources;

use App\Models\ActivityPub\LocalActor;
use App\Traits\Resources\ActivityPubResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OutboxCollection extends ResourceCollection
{
    use ActivityPubResource;

    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = OutboxResource::class;

    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = null;

    public LocalActor $actor;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $context = [
            '@context' => [
                'https://www.w3.org/ns/activitystreams', [
                    'ostatus' => 'http://ostatus.org#',
                    'atomUri' => 'ostatus:atomUri',
                    'inReplyToAtomUri' => 'ostatus:inReplyToAtomUri',
                    'conversation' => 'ostatus:conversation',
                    'sensitive' => 'as:sensitive',
                    'toot' => 'http://joinmastodon.org/ns#',
                    // 'votersCount' => 'toot:votersCount', // Only for polls
                    'Hashtag' => 'as:Hashtag',
                    // ONLY FOR PICS WITH ATTACHMENT
                    // 'blurhash' => 'toot:blurhash',
                    // 'focalPoint' => [
                    //     '@container' => '@list',
                    //     '@id' => 'toot:focalPoint',
                    // ],
                    'Emoji' => 'toot:Emoji',
                ],
            ],
        ];
        // TODO: fix this

        $collectionPage = [
              'id' => $this->resource->url($this->resource->currentPage()),
              'type' => 'OrderedCollectionPage',
              'next' => $this->resource->nextPageUrl(),
              'prev' => $this->resource->previousPageUrl(),
              'partOf' => route('user.outbox', [$this->actor]),
              'orderedItems' => $this->collection,
            //   "orderedItems" => $this->resource->items()
        ];

        return array_merge($context, $collectionPage);
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request)
    {
        return JsonResource::toResponse($request);
    }
}
