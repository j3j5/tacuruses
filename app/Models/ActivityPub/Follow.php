<?php

namespace App\Models\ActivityPub;

use ActivityPhp\Type;
use ActivityPhp\Type\Extended\Activity\Follow as ActivityFollow;
use App\Services\ActivityPub\Context;
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
 * @method static \Database\Factories\ActivityPub\FollowFactory factory($count = null, $state = [])
 * @property bool $accepted
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereAccepted($value)
 * @mixin \Eloquent
 */
class Follow extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'accepted' => 'bool',
    ];

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

    public function accept() : self
    {
        $this->accepted = true;
        $this->save();

        return $this;
    }

    public function getApActivity() : ActivityFollow
    {
        /** @var \ActivityPhp\Type\Extended\Activity\Follow $activity */
        $activity = Type::create('Follow', [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->activityId,
            'actor' => $this->actor->activityId,
            'object' => $this->target->activityId,
        ]);
        return $activity;
    }

}
