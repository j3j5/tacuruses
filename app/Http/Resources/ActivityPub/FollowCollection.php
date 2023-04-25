<?php

namespace App\Http\Resources\ActivityPub;

use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use App\Traits\Resources\ActivityPubResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FollowCollection extends ResourceCollection
{
    use ActivityPubResource;

    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = FollowResource::class;

    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = null;

    public LocalActor $user;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $prev = $this->resource->previousPageUrl();
        $next = $this->resource->nextPageUrl();
        return [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->resource->url($this->resource->currentPage()),
            'type' => 'OrderedCollectionPage',
            'totalItems' => $this->resource->count(),
            'next' => $this->when(!empty($next), $next),
            'prev' => $this->when(!empty($prev), $prev),
            'partOf' => route('actor.followers', [$this->user]),
            'orderedItems' => $this->collection->map(
                fn (FollowResource $follow) => $follow->getId()
            ),
        ];
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
