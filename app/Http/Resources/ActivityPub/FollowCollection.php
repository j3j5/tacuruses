<?php

declare(strict_types=1);

namespace App\Http\Resources\ActivityPub;

use ActivityPhp\Type;
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
    public bool $following = false;

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

        $collection = Type::create('OrderedCollectionPage', [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->resource->url($this->resource->currentPage()),
            'totalItems' => $this->resource->total(),
            'first' => $this->when($this->resource->hasPages(), $this->resource->url(0)),
            'last' => $this->when($this->resource->hasPages(), $this->resource->url($this->resource->lastPage())),
            'prev' => $this->when(!empty($prev), $prev),
            'current' => $this->when($this->resource->hasPages(), $this->resource->url($this->resource->currentPage())),
            'next' => $this->when(!empty($next), $next),
            'partOf' => $this->following
                ? route('actor.following', [$this->user])
                : route('actor.followers', [$this->user]),
            'orderedItems' => $this->collection->pluck('activityId'),
        ]);

        return $collection->toArray();
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
