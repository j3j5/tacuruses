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
 * @property array $object
 * @property bool $accepted
 * @property-read \App\Models\ActivityPub\Actor $actor
 * @property-read string $slug
 * @property-read \App\Models\ActivityPub\LocalActor|null $target
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityFollow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityFollow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityFollow query()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityFollow whereAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityFollow whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityFollow whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityFollow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityFollow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityFollow whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityFollow whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityFollow whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityFollow whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityFollow whereUpdatedAt($value)
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
