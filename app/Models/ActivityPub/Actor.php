<?php

declare(strict_types = 1);

namespace App\Models\ActivityPub;

use App\Scopes\Activities\Accepted;
use App\Scopes\Notes\IsDraft;
use App\Scopes\Notes\IsNotReply;
use App\Scopes\Notes\Published;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Parental\HasChildren;
use phpseclib3\Crypt\Common\PublicKey as CommonPublicKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PublicKey;
use RuntimeException;

use function Safe\parse_url;

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
 * @property string $activityId
 * @property string|null $type
 * @property string $url
 * @property string $inbox
 * @property string|null $sharedInbox
 * @property string $publicKeyId
 * @property string $publicKey
 * @property string $actor_type
 * @property string $followers_url
 * @property string $following_url
 * @property string|null $outbox
 * @property-read string $domain
 * @property-read string $canonical_username
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $allNotes
 * @property-read int|null $all_notes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $drafts
 * @property-read int|null $drafts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Follow> $following
 * @property-read int|null $following_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $liked
 * @property-read int|null $liked_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $notes
 * @property-read int|null $notes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shared
 * @property-read int|null $shared_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shares
 * @property-read int|null $shares_count
 * @method static \Illuminate\Database\Eloquent\Builder|Actor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Actor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Actor query()
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereActorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereAlsoKnownAs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereFollowersUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereFollowingUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereHeader($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereInbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereOutbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor wherePublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor wherePublicKeyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereSharedInbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Actor whereUsername($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $notesWithReplies
 * @property-read int|null $notes_with_replies_count
 * @property-read \phpseclib3\Crypt\Common\PublicKey $public_key_object
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Actor> $followers
 * @property-read int|null $followers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Follow> $follows
 * @property-read int|null $follows_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Follow> $receivedFollows
 * @property-read int|null $received_follows_count
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

    /**
     *
     */
    public function follows() : HasMany
    {
        return $this->hasMany(Follow::class)->tap(new Accepted());
    }

    /**
     *
     */
    public function receivedFollows() : HasMany
    {
        return $this->hasMany(Follow::class, 'target_id')->tap(new Accepted());
    }

    /**
     *
     */
    public function followers() : HasManyThrough
    {
        return $this->hasManyThrough(
            Actor::class,
            Follow::class,
            'target_id',
            'id',
            'id',
            'actor_id',
        )->tap(new Accepted());
    }

    /**
     *
     */
    public function following() : HasManyThrough
    {
        return $this->hasManyThrough(
            Actor::class,
            Follow::class,
            'actor_id',
            'id',
            'id',
            'target_id',
        )->tap(new Accepted());
    }

    /**
     *
     */
    public function liked() : HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     *
     */
    public function shares() : HasManyThrough
    {
        return $this->hasManyThrough(
            Share::class,
            LocalNote::class,
            'actor_id',
            'target_id'
        );
    }

    /**
     *
     */
    public function notes() : HasMany
    {
        return $this->allNotes()
            ->tap(new Published())
            ->tap(new IsNotReply());
    }

    /**
     *
     */
    public function notesWithReplies() : HasMany
    {
        return $this->allNotes()->tap(new Published());
    }

    /**
     *
     */
    public function drafts() : HasMany
    {
        return $this->allNotes()->tap(new IsDraft());
    }

    /**
     *
     */
    public function allNotes() : HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     *
     */
    public function shared() : HasMany
    {
        return $this->hasMany(Share::class);
    }

    public function domain() : Attribute
    {
        return Attribute::make(
            get: fn () : string => parse_url($this->activityId, PHP_URL_HOST) /** @phpstan-ignore-line */
        );
    }

    public function canonicalUsername() : Attribute
    {
        return Attribute::make(
            get: fn () : string => '@' . $this->username . '@' . $this->domain
        );
    }

    public function publicKeyObject() : Attribute
    {
        return Attribute::make(
            get: function () : CommonPublicKey {
                $publicKey = PublicKeyLoader::load($this->publicKey);
                if (!$publicKey instanceof CommonPublicKey) {
                    throw new RuntimeException('invalid key type');
                }
                if ($publicKey instanceof PublicKey) {
                    // Mastodon uses the relaxed padding
                    $publicKey = $publicKey->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);
                }

                return $publicKey;
            }
        );
    }
}
