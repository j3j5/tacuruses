<?php

declare(strict_types = 1);

namespace App\Models\ActivityPub;

use App\Enums\ActivityTypes;
use App\Events\LocalActorUnfollowed;
use App\Exceptions\AppException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;

/**
 * App\Models\ActivityPub\ActivityUndo
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityUndo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityUndo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityUndo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityUndo whereAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityUndo whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityUndo whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityUndo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityUndo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityUndo whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityUndo whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityUndo whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityUndo whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityUndo whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActivityUndo extends Activity
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
        return match (ActivityTypes::tryFrom((string) $this->object_type)) {
            ActivityTypes::FOLLOW => $this->belongsTo(LocalActor::class, 'target_id'),
            ActivityTypes::LIKE, ActivityTypes::UNDO, ActivityTypes::ANNOUNCE => $this->belongsTo(LocalNote::class, 'target_id'),
            default => throw new AppException('Unknown Undo type "' . $this->object_type . '"'),
        };
    }

    public function markAsAccepted() : self
    {
        parent::markAsAccepted();

        match (ActivityTypes::tryFrom((string) $this->object_type)) {
            ActivityTypes::FOLLOW => LocalActorUnfollowed::dispatch($this),
            ActivityTypes::LIKE, ActivityTypes::UNDO, ActivityTypes::ANNOUNCE => '', // TODO: delete associated notification!
            default => throw new AppException('Unknown Undo type "' . $this->object_type . '"'),
        };
        return $this;
    }

}
