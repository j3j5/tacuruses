<?php

namespace App\Models\ActivityPub;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasChildren;
use Vinkla\Hashids\Facades\Hashids;

/**
 * App\Models\ActivityPub\Action
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Activity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Activity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Activity query()
 * @property int $id
 * @property string $activityId
 * @property string $type
 * @property int|null $actor_id
 * @property int $target_id
 * @property string|null $object_type
 * @property array $object
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereUpdatedAt($value)
 * @property-read string $slug
 * @property-read \App\Models\ActivityPub\Actor|null $actor
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereAccepted($value)
 * @mixin \Eloquent
 */
class Activity extends Model
{
    use HasFactory;
    use HasChildren;

    protected $fillable = ['activityId', 'type', 'object'];
    protected string $childColumn = 'type';

    /** @var array<string, class-string> */
    protected array $childTypes = [
        'Follow' => ActivityFollow::class,
        'Like' => ActivityLike::class,
        'Undo' => ActivityUndo::class,
        'Announce' => ActivityAnnounce::class,
    ];

    public function slug() : Attribute
    {
        return Attribute::make(
            get: fn () : string => Hashids::encode($this->id)
        );
    }

    public function actor() : BelongsTo
    {
        return $this->belongsTo(Actor::class, 'actor_id');
    }
}
