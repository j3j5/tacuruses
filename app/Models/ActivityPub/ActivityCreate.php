<?php

declare(strict_types = 1);

namespace App\Models\ActivityPub;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;

/**
 * App\Models\ActivityPub\ActivityCreate
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $activityId
 * @property string $type
 * @property int $actor_id
 * @property int $target_id
 * @property string|null $object_type
 * @property array<array-key, mixed> $object
 * @property bool $accepted
 * @property-read \App\Models\ActivityPub\Actor $actor
 * @property-read string $slug
 * @property-read \App\Models\ActivityPub\RemoteNote|null $target
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCreate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCreate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCreate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCreate whereAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCreate whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCreate whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCreate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCreate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCreate whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCreate whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCreate whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCreate whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCreate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActivityCreate extends Activity
{
    use HasFactory;
    use HasParent;

    public function target() : BelongsTo
    {
        return $this->belongsTo(RemoteNote::class, 'target_id');
    }
}
