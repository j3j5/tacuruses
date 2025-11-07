<?php

declare(strict_types=1);

namespace App\Models\ActivityPub;

use App\Events\LocalActorFollowed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;

/**
 * App\Models\ActivityPub\ActivityFollow
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
 * @property-read \App\Models\ActivityPub\LocalActor|null $target
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityFollow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityFollow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityFollow query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityFollow whereAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityFollow whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityFollow whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityFollow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityFollow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityFollow whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityFollow whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityFollow whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityFollow whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityFollow whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActivityFollow extends Activity
{
    use HasFactory;
    use HasParent;

    public function target() : BelongsTo
    {
        return $this->belongsTo(LocalActor::class, 'target_id');
    }

    public function markAsAccepted(): ActivityFollow
    {
        parent::markAsAccepted();
        LocalActorFollowed::dispatch($this);
        return $this;
    }
}
