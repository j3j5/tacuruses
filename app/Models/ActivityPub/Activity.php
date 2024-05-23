<?php

declare(strict_types=1);

namespace App\Models\ActivityPub;

use App\Enums\ActivityTypes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasChildren;
use Vinkla\Hashids\Facades\Hashids;

/**
 * App\Models\ActivityPub\Activity
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
 * @method static \Illuminate\Database\Eloquent\Builder|Activity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Activity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Activity query()
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Activity whereUpdatedAt($value)
 * @method static \Database\Factories\ActivityPub\ActivityFactory factory($count = null, $state = [])
 * @mixin \Eloquent
 */
class Activity extends Model
{
    use HasFactory;
    use HasChildren;

    /** @var string[] */
    protected $guarded = ['id', 'created_at', 'updated_at', 'accepted'];

    /** @var string */
    protected string $childColumn = 'type';

    /** @var array<string, class-string> */
    protected array $childTypes = [
        ActivityTypes::ACCEPT->value => ActivityAccept::class,
        ActivityTypes::ANNOUNCE->value => ActivityAnnounce::class,
        ActivityTypes::CREATE->value => ActivityCreate::class,
        ActivityTypes::FOLLOW->value => ActivityFollow::class,
        ActivityTypes::LIKE->value => ActivityLike::class,
        ActivityTypes::UNDO->value => ActivityUndo::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accepted' => 'boolean',
            'object' => 'array',
        ];
    }

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

    public function markAsAccepted() : self
    {
        $this->accepted = true;
        $this->save();

        return $this;
    }

}
