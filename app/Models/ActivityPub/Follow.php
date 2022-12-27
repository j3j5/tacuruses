<?php

namespace App\Models\ActivityPub;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

/**
 * App\Models\ActivityPub\Follower
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Follow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Follow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Follow query()
 * @mixin \Eloquent
 * @property int $id
 * @property int $actor_id
 * @property int $target_id
 * @property string $remote_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereRemoteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereUpdatedAt($value)
 * @property-read string $slug
 */
class Follow extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $fillable = ['actor_id', 'target_id',  'remote_id'];

    public function slug() : Attribute
    {
        return Attribute::make(
            get: fn () : string => Hashids::encode($this->id)
        );
    }
}
