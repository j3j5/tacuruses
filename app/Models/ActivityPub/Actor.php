<?php

namespace App\Models\ActivityPub;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Parental\HasChildren;

/**
 * App\Models\ActivityPub\Actor
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property string $username
 * @property string|null $avatar
 * @property string|null $header
 * @property string|null $bio
 * @property string|null $alsoKnownAs
 * @property string|null $properties
 * @property string $language
 * @property string|null $activityId
 * @property string|null $type
 * @property string|null $url
 * @property string $inbox
 * @property string|null $sharedInbox
 * @property string|null $publicKeyId
 * @property string|null $publicKey
 * @property string|null $actor_type
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Follow> $following
 * @property-read int|null $following_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $liked
 * @property-read int|null $liked_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shared
 * @property-read int|null $shared_count
 * @method static \Illuminate\Database\Eloquent\Builder|Actor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Actor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Actor query()
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereActorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereAlsoKnownAs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereHeader($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereInbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor wherePublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor wherePublicKeyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereSharedInbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereUsername($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Follow> $following
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $liked
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shared
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Follow> $following
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $liked
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shared
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Follow> $following
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $liked
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shared
 * @mixin \Eloquent
 */
class Actor extends Model
{
    use HasFactory;
    use HasChildren;

    protected $fillable = ['type', 'actor_type'];

    /** @var array<string, class-string> */
    protected array $childTypes = [
        'local' => LocalActor::class,
        'remote' => RemoteActor::class,
    ];

    protected string $childColumn = 'actor_type';

    public function following() : HasMany
    {
        return $this->hasMany(Follow::class);
    }

    public function liked() : HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function shared() : HasMany
    {
        return $this->hasMany(Share::class);
    }
}
