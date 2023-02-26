<?php

namespace App\Models\ActivityPub;

use App\Domain\ActivityPub\Announce;
use App\Domain\ActivityPub\Follow;
use App\Domain\ActivityPub\Like;
use App\Domain\ActivityPub\Undo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;
use RuntimeException;

/**
 * App\Models\ActivityPub\ActivityUndo
 *
 * @property int $id
 * @property string $activityId
 * @property string $type
 * @property int|null $actor_id
 * @property int $target_id
 * @property string|null $object_type
 * @property array $object
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $accepted
 * @property-read \App\Models\ActivityPub\Actor|null $actor
 * @property-read \App\Models\ActivityPub\Note|\App\Models\ActivityPub\LocalActor|null $target
 * @property-read string $slug
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityUndo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityUndo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityUndo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityUndo whereAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityUndo whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityUndo whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityUndo whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityUndo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityUndo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityUndo whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityUndo whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityUndo whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityUndo whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityUndo whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActivityUndo extends Activity
{
    use HasFactory;
    use HasParent;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'object' => 'array',
    ];

    public function target() : BelongsTo
    {
        return match ($this->object_type) {
            Follow::TYPE => $this->belongsTo(LocalActor::class, 'target_id'),
            Like::TYPE, Undo::TYPE, Announce::TYPE => $this->belongsTo(Note::class, 'target_id'),
            default => throw new RuntimeException('Unknown UNDO type "' . $this->object_type . '"'),
        };
    }
}
