<?php

namespace App\Models\ActivityPub;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vinkla\Hashids\Facades\Hashids;

/**
 * App\Models\ActivityPub\Like
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
 * @method static \Illuminate\Database\Eloquent\Builder|Like newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Like newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Like query()
 * @method static \Illuminate\Database\Eloquent\Builder|Like whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Like whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Like whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Like whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Like whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Like whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Like extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $fillable = ['actor_id', 'target_id'];

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
