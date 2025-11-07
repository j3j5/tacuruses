<?php

declare(strict_types = 1);

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
 * @property array<array-key, mixed> $object
 * @property bool $accepted
 * @property-read \App\Models\ActivityPub\Actor $actor
 * @property-read string $slug
 * @property-read \App\Models\ActivityPub\LocalNote|null $target
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAnnounce newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAnnounce newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAnnounce query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAnnounce whereAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAnnounce whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAnnounce whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAnnounce whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAnnounce whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAnnounce whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAnnounce whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAnnounce whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAnnounce whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAnnounce whereUpdatedAt($value)
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
