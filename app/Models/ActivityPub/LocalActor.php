<?php

declare(strict_types = 1);

namespace App\Models\ActivityPub;

use ActivityPhp\Type;
use ActivityPhp\Type\Extended\Activity\Update;
use App\Domain\ActivityPub\Mastodon\AbstractActor;
use App\Enums\ActorTypes;
use App\Jobs\ActivityPub\DeliverActivity;
use App\Models\Notification;
use App\Services\ActivityPub\Context;
use App\Traits\Notifiable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Parental\HasParent;
use phpseclib3\Crypt\RSA;
use RuntimeException;

use function Safe\preg_match;
use Stevebauman\Purify\Casts\PurifyHtmlOnGet;

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
 * @property string $activityId
 * @property string $url
 * @property string $inbox
 * @property string|null $sharedInbox
 * @property string $publicKeyId
 * @property string $publicKey
 * @property string $actor_type
 * @property string $followers_url
 * @property string $following_url
 * @property string $outbox
 * @property-read string $activity_id
 * @property ActorTypes|null $type
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\LocalNote> $allNotes
 * @property-read int|null $all_notes_count
 * @property-read string $domain
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\LocalNote> $drafts
 * @property-read int|null $drafts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Actor> $followers
 * @property-read int|null $followers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Actor> $following
 * @property-read int|null $following_count
 * @property-read string $key_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $liked
 * @property-read int|null $liked_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $likes
 * @property-read int|null $likes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $mentions
 * @property-read int|null $mentions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\LocalNote> $notes
 * @property-read int|null $notes_count
 * @property-read string $privateKey
 * @property-read string $private_key
 * @property-read string $publicKey
 * @property-read string $public_key
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shared
 * @property-read int|null $shared_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shares
 * @property-read int|null $shares_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
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
 * @property-read string $canonical_username
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $notesWithReplies
 * @property-read int|null $notes_with_replies_count
 * @method static \Database\Factories\ActivityPub\LocalActorFactory factory($count = null, $state = [])
 * @property-read \phpseclib3\Crypt\Common\PublicKey $public_key_object
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Notification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Follow> $follows
 * @property-read int|null $follows_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Follow> $receivedFollows
 * @property-read int|null $received_follows_count
 * @property-read string $shared_inbox
 * @property-read string $public_key_id
 * @mixin \Eloquent
 */
class LocalActor extends Actor implements
    AuthenticatableContract,
    AuthorizableContract
{
    use Authenticatable, Authorizable, Notifiable;
    use HasFactory;
    use HasParent;
    use HasApiTokens;

    public const USER_REGEX = '#^https://(?<domain>[\w\.\_\-]+)/(?<user>[\w\.\_\-]+)$#';

    protected $fillable = ['actor_type'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'alsoKnownAs' => 'array',
            'properties' => 'array',
            'bio' => PurifyHtmlOnGet::class . ':mastodon',
            'type' => ActorTypes::class,
        ];
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'username';
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
        return $this->belongsToMany(Note::class)->withTimestamps();
    }

    public function avatar() : Attribute
    {
        return Attribute::make(
            get: function ($value) : string {
                if (empty($value)) {
                    return asset('/img/default_avatar.svg');
                }

                if (Str::startsWith($value, 'http')) {
                    return $value;
                }

                return Storage::cloud()->url($value);
            }
        );
    }

    public function header() : Attribute
    {
        return Attribute::make(
            get: function ($value) : string {
                if (empty($value)) {
                    return asset('/img/default_header.svg');
                }

                if (Str::startsWith($value, 'http')) {
                    return $value;
                }

                return Storage::cloud()->url($value);
            }
        );
    }

    public function activityId() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('actor.show', [$this]),
        );
    }

    public function publicKeyId() : Attribute
    {
        return Attribute::make(
            get: fn () : string => $this->activityId . '#main-key',
        );
    }

    public function publicKey() : Attribute
    {
        return Attribute::make(
            get: fn () : string => (string) Storage::disk('keys')->get("{$this->id}/public.pem"),
        );
    }

    public function privateKey() : Attribute
    {
        return Attribute::make(
            get: fn () : string => (string) Storage::disk('keys')->get("{$this->id}/private.pem"),
        );
    }

    public function url() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('actor.show', [$this]),
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

    public function sharedInbox() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('shared-inbox')
        );
    }

    public function getAPActor() : AbstractActor
    {
        $context = ['@context' => Context::$actor];

        $actor = $this->getActorArray();

        /** @phpstan-ignore-next-line */
        return Type::create($this->type->value, array_merge($context, $actor));
    }

    public function getAPUpdate() : Update
    {
        $context = Context::$actor;
        $actor = $this->getActorArray();
        /** @var \ActivityPhp\Type\Extended\Activity\Update $update */
        $update = Type::create('Update', [
            '@context' => $context,
            'id' => route('actor.show', [$this]) . '#updates/' . \time(),
            'actor' => $this->activityId,
            // TODO: should it change depending on visibility of the account?
            'to' => [Context::ACTIVITY_STREAMS_PUBLIC],
            'object' => $actor,
        ]);

        return $update;
    }

    private function getActorArray() : array
    {
        $actor = [
            'id' => $this->activityId,
            'preferredUsername' => $this->username,
            'url' => $this->url,
            'name' => $this->name,
            'summary' => $this->bio,
            // Avatar
            'icon' => [
                'type' => 'Image',
                'mediaType' => 'image/jpeg',
                'url' => $this->avatar,
            ],
            // Header
            'image' => [
                'type' => 'Image',
                'mediaType' => 'image/jpeg',
                'url' => $this->header,
            ],
        ];

        $metadata = [
            'tag' => Arr::get($this->properties, 'tag', []),
            'attachment' => Arr::get($this->properties, 'attachment', []),
            // TODO: move to properties
            'discoverable' => true,
            'indexable' => true,
            // Crypto to sign messages
            'publicKey' => [
                'id' => $this->publicKeyId,
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
            'manuallyApprovesFollowers' => false,   // TODO: Move to the DB
            'endpoints' => [
                'sharedInbox' => $this->sharedInbox,
            ],
        ];

        return array_merge($actor, $metadata, $links);
    }

    public function follow(Actor $target) : self
    {
        if ($this->relationLoaded('following')) {
            $alreadyFollowing = $this->following->contains('target_id', $target->id);
        } else {
            $alreadyFollowing = Follow::where('actor_id', $this->id)->where('target_id', $target->id)->exists();
        }

        if ($alreadyFollowing) {
            Log::debug('@' . $this->username . ' already follows ' . $target->canonical_username);
            return $this;
        }

        // Store the follow
        $follow = Follow::create([
            'actor_id' => $this->id,
            'target_id' => $target->id,
            'activityId' => url('/' . Str::uuid()),
        ]);

        $activity = $follow->getApActivity();

        Log::debug('dispatching job to deliver the Follow activity for an actor', [
            'actor' => $this->id,
            'target' => $target->id,
        ]);

        DeliverActivity::dispatch($this, $activity, $target->inbox);

        return $this;
    }

    public function unfollow(Actor $target) : self
    {
        if ($this->relationLoaded('follows')) {
            $follow = $this->follows->firstWhere('id', $target->id);
            $alreadyFollowing = $follow !== null;
        } elseif ($this->relationLoaded('following')) {
            $following = $this->following->firstWhere('id', $target->id);
            $alreadyFollowing = false;
            $follow = null;
            if ($following) {
                $alreadyFollowing = true;
                $follow = Follow::findOrFail($following->laravel_through_key); // @phpstan-ignore-line
            }
        } else {
            $follow = $this->follows()->where('target_id', $target->id)->first();
            $alreadyFollowing = $follow !== null;
        }

        if (!$alreadyFollowing) {
            Log::debug('@' . $this->username . ' is currently not following ' . $target->canonical_username);
            return $this;
        }

        /** @var \App\Models\ActivityPub\Follow $follow */
        /** @var \ActivityPhp\Type\Extended\Activity\Undo $activity */
        $activity = Type::create('Undo', [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => route('actor.show', [$this]) . '#follows/' . $follow->slug . '/undo',
            'actor' => $this->activityId,
            'object' => $follow->getAPActivity(),
        ]);
        $follow->delete();

        Log::debug('dispatching job to deliver the Follow activity for an actor', [
            'actor' => $this->id,
            'target' => $target->id,
        ]);
        DeliverActivity::dispatch($this, $activity, $target->inbox);

        return $this;
    }

    public function generateKeys() : self
    {
        if (Storage::disk('keys')->exists("{$this->id}/private.pem")) {
            throw new RuntimeException('This actor already has keys');
        }
        $keyLength = 2048;
        $privateKey = RSA::createKey($keyLength);
        Storage::disk('keys')->put("{$this->id}/private.pem", $privateKey->toString('PKCS1'));
        Storage::disk('keys')->put("{$this->id}/public.pem", $privateKey->getPublicKey()->toString('PKCS1'));

        return $this;
    }

    public function scopeByActivityId(Builder $query, string $activityId) : void
    {
        $matches = [];
        if (preg_match(self::USER_REGEX, $activityId, $matches) === 0) {
            throw new RuntimeException('ID not found in provided ActivityID: ' . $activityId);
        }
        $query->where('username', $matches['user']);    // @phpstan-ignore offsetAccess.nonOffsetAccessible ()
    }

}
