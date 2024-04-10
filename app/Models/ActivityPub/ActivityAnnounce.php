<?php

namespace App\Models\ActivityPub;

use App\Events\LocalNoteShared;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;

/**
 * App\Models\ActivityPub\ActivityAnnounce
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
 * @property-read \App\Models\ActivityPub\LocalNote|null $target
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAnnounce newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAnnounce newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAnnounce query()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAnnounce whereAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAnnounce whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAnnounce whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAnnounce whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAnnounce whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAnnounce whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAnnounce whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAnnounce whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAnnounce whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAnnounce whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActivityAnnounce extends Activity
{
    use HasFactory;
    use HasParent;

    public function target() : BelongsTo
    {
        return $this->belongsTo(LocalNote::class, 'target_id');
    }

    public function markAsAccepted(): ActivityAnnounce
    {
        parent::markAsAccepted();
        LocalNoteShared::dispatch($this);
        return $this;
    }
}
