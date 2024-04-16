<?php

declare(strict_types = 1);

namespace App\Models\ActivityPub;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vinkla\Hashids\Facades\Hashids;

/**
 * App\Models\ActivityPub\Share
 *
 * @property int $id
 * @property int $actor_id
 * @property int $target_id
 * @property string $activityId
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ActivityPub\Actor $actor
 * @property-read string $slug
 * @property-read \App\Models\ActivityPub\LocalNote $target
 * @method static \Illuminate\Database\Eloquent\Builder|Share newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Share newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Share query()
 * @method static \Illuminate\Database\Eloquent\Builder|Share whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Share whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Share whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Share whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Share whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Share whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Share extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function actor() : BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }

    public function target() : BelongsTo
    {
        return $this->belongsTo(LocalNote::class, 'target_id');
    }

    public function slug() : Attribute
    {
        return Attribute::make(
            get: fn () : string => Hashids::encode($this->id)
        );
    }
}
