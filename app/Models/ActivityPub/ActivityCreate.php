<?php

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
 * @property array $object
 * @property bool $accepted
 * @property-read \App\Models\ActivityPub\Actor $actor
 * @property-read string $slug
 * @property-read \App\Models\ActivityPub\RemoteNote $target
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityCreate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityCreate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityCreate query()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityCreate whereAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityCreate whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityCreate whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityCreate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityCreate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityCreate whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityCreate whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityCreate whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityCreate whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityCreate whereUpdatedAt($value)
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
