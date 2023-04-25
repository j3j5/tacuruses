<?php

namespace App\Http\Resources\ActivityPub;

use ActivityPhp\Type;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
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
        $collectionPage = Type::create('OrderedCollectionPage', [
            'id' => $this->resource->url($this->resource->currentPage()),
            '@context' => [
                Context::ACTIVITY_STREAMS,
                Context::$status,
            ],
            'next' => $this->resource->nextPageUrl(),
            'prev' => $this->resource->previousPageUrl(),
            'partOf' => route('actor.outbox', [$this->actor]),
            'orderedItems' => $this->collection,
      ]);

        return $collectionPage->toArray();
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
