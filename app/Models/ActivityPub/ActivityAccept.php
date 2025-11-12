<?php

declare(strict_types = 1);

namespace App\Models\ActivityPub;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;
use RuntimeException;

/**
 * App\Models\ActivityPub\ActivityAccept
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
 * @property int $accepted
 * @property-read \App\Models\ActivityPub\Actor $actor
 * @property-read string $slug
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAccept newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAccept newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAccept query()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAccept whereAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAccept whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAccept whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAccept whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAccept whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAccept whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAccept whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAccept whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAccept whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAccept whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActivityAccept extends Activity
{
    use HasFactory;
    use HasParent;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'object' => 'array',
        ];
    }

    public function target() : BelongsTo
    {
        return match ($this->object_type) {
            'Follow' => $this->belongsTo(Actor::class, 'target_id'),
            'Like', 'Undo', 'Announce' => $this->belongsTo(Note::class, 'target_id'),
            default => throw new RuntimeException('Unknown Accept type "' . $this->object_type . '"'),
        };
    }

}
