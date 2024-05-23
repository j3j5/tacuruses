<?php

declare(strict_types=1);

namespace App\Http\Resources\ActivityPub;

use ActivityPhp\Type;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 *
 * @mixin \App\Models\Media
 */
class AttachmentResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return Type::create('Document', [
            'mediaType' => $this->content_type,
            'url' => $this->remote_url,
            'name' => $this->description,
            'blurhash' => $this->hash,
            'focalPoint' => [
                0.0,
                0.0,
            ],
            'width' => $this->width,
            'height' => $this->height,
        ])->toArray();
    }

}
