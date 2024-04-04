<?php

namespace App\Http\Resources\API\Mastodon;

use Illuminate\Http\Resources\Json\JsonResource;

class StatusResource extends JsonResource
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
            "id" => $this->id,
            "original_content" => $this->original_content,
            "contentMap" => $this->contentMap,
            "replyTo_id" => $this->replyTo_id,
            "sensitive" => $this->sensitive,
            "summary" => $this->summary,
            "visibility" => $this->visibility,
            "to" => $this->to,
            "cc" => $this->cc,
            "tags" => $this->tags,
            "published_at" => $this->published_at,
        ];
    }
}
