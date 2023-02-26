<?php

namespace App\Models\ActivityPub;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vinkla\Hashids\Facades\Hashids;

/**
 * App\Models\ActivityPub\Follow
 *
 * @property int $id
 * @property int $actor_id
 * @property int $target_id
 * @property string $activityId
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ActivityPub\Actor $actor
 * @property-read string $slug
 * @property-read \App\Models\ActivityPub\Actor $target
 * @method static \Illuminate\Database\Eloquent\Builder|Follow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Follow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Follow query()
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Follow extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function actor() : BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }

    public function target() : BelongsTo
    {
        return $this->belongsTo(Actor::class, 'target_id');
    }

    public function slug() : Attribute
    {
        return Attribute::make(
            get: fn () : string => Hashids::encode($this->id)
        );
    }
}
