<?php

declare(strict_types = 1);

namespace App\Models\ActivityPub;

use App\Events\LocalNoteLiked;
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
 * @property array<array-key, mixed> $object
 * @property bool $accepted
 * @property-read \App\Models\ActivityPub\Actor $actor
 * @property-read string $slug
 * @property-read \App\Models\ActivityPub\LocalNote|null $target
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLike newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLike newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLike query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLike whereAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLike whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLike whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLike whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLike whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLike whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLike whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLike whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLike whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLike whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActivityLike extends Activity
{
    use HasFactory;
    use HasParent;

    public function target() : BelongsTo
    {
        return $this->belongsTo(LocalNote::class, 'target_id');
    }

    public function markAsAccepted(): ActivityLike
    {
        parent::markAsAccepted();
        LocalNoteLiked::dispatch($this);
        return $this;
    }
}
