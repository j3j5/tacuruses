<?php

namespace App\Models\ActivityPub;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ActivityPub\Action
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Action newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Action newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Action query()
 * @mixin \Eloquent
 * @property int $id
 * @property string $activityId
 * @property string $type
 * @property int|null $actor_id
 * @property int $target_id
 * @property string|null $object_type
 * @property array $object
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Action whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Action whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Action whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Action whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Action whereObject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Action whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Action whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Action whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Action whereUpdatedAt($value)
 */
class Action extends Model
{
    use HasFactory;

    protected $fillable = ['activityId', 'type', 'object'];

    protected $childColumn = 'actor_type';

    protected $casts = [
        'object' => 'array',
    ];
}
