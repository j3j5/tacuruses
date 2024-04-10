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

}
