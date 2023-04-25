<?php

namespace App\Models\ActivityPub;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Parental\HasParent;
use RuntimeException;

use function Safe\parse_url;
use function Safe\preg_match;

/**
 * App\Models\ActivityPub\LocalActor
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property string $username
 * @property string $avatar
 * @property string $header
 * @property string|null $bio
 * @property array|null $alsoKnownAs
 * @property array|null $properties
 * @property string $language
 * @property string|null $activityId
 * @property string|null $type
 * @property string $url
 * @property string $inbox
 * @property string|null $sharedInbox
 * @property string|null $publicKeyId
 * @property string|null $publicKey
 * @property string|null $actor_type
 * @property string $followers_url
 * @property string $following_url
 * @property string $outbox
 * @property-read string $activity_id
 * @property-read string $avatar_url
 * @property-read string $domain
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Follow> $followers
 * @property-read int|null $followers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Follow> $following
 * @property-read int|null $following_count
 * @property-read string $full_username
 * @property-read string $header_url
 * @property-read string $key_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $liked
 * @property-read int|null $liked_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $likes
 * @property-read int|null $likes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $mentions
 * @property-read int|null $mentions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\LocalNote> $notes
 * @property-read int|null $notes_count
 * @property-read string $private_key
 * @property-read string $public_key
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shared
 * @property-read int|null $shared_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shares
 * @property-read int|null $shares_count
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor byActivityId(string $activityId)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor query()
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereActorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereAlsoKnownAs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereFollowersUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereFollowingUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereHeader($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereInbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereOutbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor wherePublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor wherePublicKeyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereSharedInbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereUsername($value)
 * @mixin \Eloquent
 */
class LocalActor extends Actor
{
    use HasFactory;
    use HasParent;

    public const USER_REGEX = '#^https://(?<domain>[\w\.\_\-]+)/(?<user>[\w\.\_\-]+)$#';

    protected $fillable = ['actor_type'];

    protected $casts = [
        'alsoKnownAs' => 'array',
        'properties' => 'array',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'username';
    }

    public function followers() : HasMany
    {
        return $this->hasMany(Follow::class, 'target_id');
    }

    public function following() : HasMany
    {
        return $this->hasMany(Follow::class, 'target_id');
    }

    public function likes() : HasManyThrough
    {
        return $this->hasManyThrough(
            Like::class,
            LocalNote::class,
            'actor_id',
            'target_id'
        );
    }

    public function mentions() : BelongsToMany
    {
        return $this->belongsToMany(Note::class);
    }

    public function avatar() : Attribute
    {
        return Attribute::make(
            get: fn ($value) : string => !empty($value) ? Storage::cloud()->url($value) : asset('/img/default_avatar.svg'),
        );
    }

    public function header() : Attribute
    {
        return Attribute::make(
            get: fn ($value) : string => !empty($value) ? Storage::cloud()->url($value) : asset('/img/default_avatar.svg'),
        );
    }

    public function activityId() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('actor.show', [$this]),
        );
    }

    public function keyId() : Attribute
    {
        return Attribute::make(
            get: fn () : string => $this->activityId . '#main-key',
        );
    }

    public function publicKey() : Attribute
    {
        return Attribute::make(
            get: fn () : string => (string) Storage::disk('local')->get("keys/local/{$this->id}/public.pem"),
        );
    }

    public function privateKey() : Attribute
    {
        return Attribute::make(
            get: fn () : string => (string) Storage::disk('local')->get("keys/local/{$this->id}/private.pem"),
        );
    }

    public function url() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('actor.show', [$this]),
        );
    }

    public function domain() : Attribute
    {
        return Attribute::make(
            get: fn () : string => parse_url(config('app.url'), PHP_URL_HOST), /* @phpstan-ignore-line */
        );
    }

    public function fullUsername() : Attribute
    {
        return Attribute::make(
            get: fn () : string => '@' . $this->username . '@' . $this->domain,
        );
    }

    public function inbox() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('actor.inbox', [$this]),
        );
    }

    public function outbox() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('actor.outbox', [$this]),
        );
    }

    public function followingUrl() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('actor.following', [$this]),
        );
    }

    public function followersUrl() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('actor.followers', [$this]),
        );
    }

    public function avatarUrl() : Attribute
    {
        return Attribute::make(
            get: fn () : string => asset($this->avatar),
        );
    }

    public function headerUrl() : Attribute
    {
        return Attribute::make(
            get: fn () : string => asset($this->header),
        );
    }

    public function getActorArray() : array
    {
        $person = [
            // 'type' => 'Person',
            'type' => 'Service', // Bot
            'id' => $this->activityId,
            'preferredUsername' => $this->username,
            'url' => $this->url,
            'name' => $this->name,
            'summary' => $this->bio,
            // Avatar
            'icon' => [
                'type' => 'Image',
                'mediaType' => 'image/jpeg',
                'url' => $this->avatar_url,
            ],
            // Header
            'image' => [
                'type' => 'Image',
                'mediaType' => 'image/jpeg',
                'url' => $this->header_url,
            ],
        ];

        $metadata = [
            'tag' => [],
            'attachment' => Arr::wrap($this->properties),
            'discoverable' => true,
            // When the bot joined the fediverse (or it was created)
            // Crypto to sign messages
            'publicKey' => [
                'id' => $this->key_id,
                'owner' => $this->activityId,
                'publicKeyPem' => $this->publicKey,
            ],
        ];
        if ($this->created_at instanceof Carbon) {
            $metadata['published'] = $this->created_at->toAtomString();
        }

        $links = [
            'inbox' => $this->inbox,
            'outbox' => $this->outbox,
            'following' => $this->following_url,
            'followers' => $this->followers_url,
            'manuallyApprovesFollowers' => false,
            'endpoints' => [
                'sharedInbox' => route('shared-inbox'),
            ],
        ];

        return array_merge($person, $metadata, $links);
    }

    public function scopeByActivityId(Builder $query, string $activityId) : void
    {
        $matches = [];
        if (preg_match(self::USER_REGEX, $activityId, $matches) === 0) {
            throw new RuntimeException('ID not found in provided ActivityID: ' . $activityId);
        }
        $query->where('username', $matches['user']);
    }
}
