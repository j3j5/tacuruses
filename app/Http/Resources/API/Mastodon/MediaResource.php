<?php

declare(strict_types=1);

namespace App\Http\Resources\API\Mastodon;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 *
 * @mixin \App\Models\Media
 */
class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'meta' => $this->meta,
            'file_updated_at' => $this->file_updated_at,
            'remote_url' => $this->remote_url,
            'hash' => $this->hash,
            'processed' => $this->processed,
        ];
    }
}
