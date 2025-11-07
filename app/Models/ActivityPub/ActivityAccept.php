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
 * @property array<array-key, mixed> $object
 * @property int $accepted
 * @property-read \App\Models\ActivityPub\Actor $actor
 * @property-read string $slug
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAccept newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAccept newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAccept query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAccept whereAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAccept whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAccept whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAccept whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAccept whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAccept whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAccept whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAccept whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAccept whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAccept whereUpdatedAt($value)
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
