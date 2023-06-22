<?php

namespace App\Http\Resources\ActivityPub;

use App\Traits\Resources\ActivityPubResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 *
 * @mixin \App\Models\ActivityPub\Follow
 */
class FollowResource extends JsonResource
{
    use ActivityPubResource;

    public bool $following = false;

    public function getId() : string
    {
        return $this->following
            ? $this->resource->target->activityId
            : $this->resource->actor->activityId;
    }
}
