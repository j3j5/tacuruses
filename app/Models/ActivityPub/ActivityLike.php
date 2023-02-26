<?php

namespace App\Models\ActivityPub;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;

/**
 * App\Models\ActivityPub\ActivityLike
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
 * @property-read \App\Models\ActivityPub\Note|null $target
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLike newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLike newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLike query()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLike whereAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLike whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLike whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLike whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLike whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLike whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLike whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLike whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLike whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLike whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActivityLike extends Activity
{
    use HasFactory;
    use HasParent;

    public function target() : BelongsTo
    {
        return $this->belongsTo(Note::class, 'target_id');
    }
}
