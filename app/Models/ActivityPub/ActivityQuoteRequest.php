<?php

declare(strict_types = 1);

namespace App\Models\ActivityPub;

use App\Events\LocalNoteQuoted;
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityQuoteRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityQuoteRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityQuoteRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityQuoteRequest whereAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityQuoteRequest whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityQuoteRequest whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityQuoteRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityQuoteRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityQuoteRequest whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityQuoteRequest whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityQuoteRequest whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityQuoteRequest whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityQuoteRequest whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActivityQuoteRequest extends Activity
{
    use HasFactory;
    use HasParent;

    public function target() : BelongsTo
    {
        return $this->belongsTo(LocalNote::class, 'target_id');
    }

    public function markAsAccepted(): ActivityQuoteRequest
    {
        parent::markAsAccepted();
        LocalNoteQuoted::dispatch($this);
        return $this;
    }
}
