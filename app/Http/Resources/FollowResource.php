<?php

namespace App\Http\Resources;

use App\Traits\Resources\ActivityPubResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 *
 * @mixin \App\Models\ActivityPub\Follow
 */
class FollowResource extends JsonResource
{
    use ActivityPubResource;

    public function getId() : string
    {
        return $this->resource->actor->activityId;
    }

}