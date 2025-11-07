<?php

declare(strict_types = 1);

namespace App\Models\ActivityPub;

use ActivityPhp\Type;
use ActivityPhp\Type\Core\Collection;
use App\Domain\ActivityPub\Mastodon\Create;
use App\Domain\ActivityPub\Mastodon\Note as ActivityNote;
use App\Domain\Feed\FeedItem;
use App\Enums\InteractionPolicy;
use App\Enums\Visibility;
use App\Events\LocalNotePublished;
use App\Events\LocalNoteUpdated;
use App\Exceptions\LocalIdException;
use App\Http\Resources\ActivityPub\AttachmentResource;
use App\Models\Media;
use App\Services\ActivityPub\Context;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Parental\HasParent;
use function Safe\json_decode;
use function Safe\json_encode;

use function Safe\preg_match;
use Spatie\Feed\Feedable;

/**
 * App\Models\ActivityPub\LocalNote
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $actor_id
 * @property string|null $replyTo_id id (PK) of the note is replying to, if any
 * @property string|null $activityId
 * @property Carbon|null $published_at
 * @property string|null $original_content
 * @property string $content
 * @property string|null $contentMap
 * @property string|null $summary On Mastodon, this field contains the visible way when sensitive is true
 * @property string|null $summaryMap
 * @property string $type Type of object, Note, Article...
 * @property bool $sensitive Mastodon-specific; content warning
 * @property array<array-key, mixed> $to array of recipients
 * @property array<array-key, mixed>|null $bto array of recipients of the blind carbon copy
 * @property array<array-key, mixed>|null $cc array of recipients of the carbon copy
 * @property array<array-key, mixed>|null $bcc array of recipients of the blind carbon copy
 * @property string|null $inReplyTo activityId of the note is replying to, if any
 * @property string|null $generator the entity that generated the object
 * @property string|null $location
 * @property Carbon|null $startTime
 * @property Carbon|null $endTime
 * @property Visibility $visibility visibility of the note, check enum Visibility
 * @property AnonymousResourceCollection $attachments
 * @property array $tags
 * @property string|null $repliesRaw
 * @property array<array-key, mixed>|null $source original representation of the content
 * @property string|null $conversation
 * @property string $note_type
 * @property Carbon|null $deleted_at
 * @property-read \App\Models\ActivityPub\Activity|null $activity
 * @property-read string $activity_id
 * @property-read string $activity_url
 * @property-read \App\Models\ActivityPub\LocalActor $actor
 * @property array $content_map
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $directReplies
 * @property-read int|null $direct_replies_count
 * @property string|null $in_reply_to
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Actor> $likeActors
 * @property-read int|null $like_actors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $likes
 * @property-read int|null $likes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Media> $mediaAttachments
 * @property-read int|null $media_attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Actor> $peers
 * @property-read int|null $peers_count
 * @property-read \App\Models\ActivityPub\Note|null $replyingTo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Actor> $shareActors
 * @property-read int|null $share_actors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shares
 * @property-read int|null $shares_count
 * @property-read string $url
 * @method static Builder<static>|LocalNote byActivityId(string $activityId)
 * @method static \Database\Factories\ActivityPub\LocalNoteFactory factory($count = null, $state = [])
 * @method static Builder<static>|LocalNote newModelQuery()
 * @method static Builder<static>|LocalNote newQuery()
 * @method static Builder<static>|LocalNote onlyTrashed()
 * @method static Builder<static>|LocalNote query()
 * @method static Builder<static>|LocalNote whereActivityId($value)
 * @method static Builder<static>|LocalNote whereActorId($value)
 * @method static Builder<static>|LocalNote whereAttachments($value)
 * @method static Builder<static>|LocalNote whereBcc($value)
 * @method static Builder<static>|LocalNote whereBto($value)
 * @method static Builder<static>|LocalNote whereCc($value)
 * @method static Builder<static>|LocalNote whereContent($value)
 * @method static Builder<static>|LocalNote whereContentMap($value)
 * @method static Builder<static>|LocalNote whereConversation($value)
 * @method static Builder<static>|LocalNote whereCreatedAt($value)
 * @method static Builder<static>|LocalNote whereDeletedAt($value)
 * @method static Builder<static>|LocalNote whereEndTime($value)
 * @method static Builder<static>|LocalNote whereGenerator($value)
 * @method static Builder<static>|LocalNote whereId($value)
 * @method static Builder<static>|LocalNote whereInReplyTo($value)
 * @method static Builder<static>|LocalNote whereLocation($value)
 * @method static Builder<static>|LocalNote whereNoteType($value)
 * @method static Builder<static>|LocalNote whereOriginalContent($value)
 * @method static Builder<static>|LocalNote wherePublishedAt($value)
 * @method static Builder<static>|LocalNote whereRepliesRaw($value)
 * @method static Builder<static>|LocalNote whereReplyToId($value)
 * @method static Builder<static>|LocalNote whereSensitive($value)
 * @method static Builder<static>|LocalNote whereSource($value)
 * @method static Builder<static>|LocalNote whereStartTime($value)
 * @method static Builder<static>|LocalNote whereSummary($value)
 * @method static Builder<static>|LocalNote whereSummaryMap($value)
 * @method static Builder<static>|LocalNote whereTags($value)
 * @method static Builder<static>|LocalNote whereTo($value)
 * @method static Builder<static>|LocalNote whereType($value)
 * @method static Builder<static>|LocalNote whereUpdatedAt($value)
 * @method static Builder<static>|LocalNote whereVisibility($value)
 * @method static Builder<static>|LocalNote withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|LocalNote withoutTrashed()
 * @mixin \Eloquent
 */
class LocalNote extends Note implements Feedable
{
    use HasFactory;
    use HasParent;

    public const NOTE_REGEX = '#^https://(?<domain>[\w\.\_\-]+)/(?:p/)(?<user>[\w\.\_\-]+)?/(?<noteId>\d+)$#';

    protected $fillable = ['type', 'note_type'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'sensitive' => 'boolean',
            'startTime' => 'datetime',
            'endTime' => 'datetime',
            'published_at' => 'datetime',
            'source' => 'array',
            'to' => 'array',
            'bto' => 'array',
            'cc' => 'array',
            'bcc' => 'array',
            'visibility' => Visibility::class,
            // Implemented manually to force array return
            // 'contentMap' => 'array',
            // 'attachments' => 'array',
            // 'tags' => 'array',
        ];
    }

    public function actor() : BelongsTo
    {
        return $this->belongsTo(LocalActor::class, 'actor_id');
    }

    public function likes() : HasMany
    {
        return $this->hasMany(Like::class, 'target_id');
    }

    public function shares() : HasMany
    {
        return $this->hasMany(Share::class, 'target_id');
    }

    public function likeActors() : HasManyThrough
    {
        return $this->hasManyThrough(
            Actor::class,
            Like::class,
            'target_id', // Foreign key on the likes table...
            'id', // Foreign key on the actors table.
            'id', // Local key on the notes table...
            'actor_id', // Local key on the likes table...
        );
    }

    public function shareActors() : HasManyThrough
    {
        return $this->hasManyThrough(
            Actor::class,
            Share::class,
            'target_id', // Foreign key on the likes table...
            'id', // Foreign key on the actors table.
            'id', // Local key on the notes table...
            'actor_id', // Local key on the likes table...
        );
    }

    public function peers() : HasManyThrough
    {
        /*
          There seem to be something weird when applying a union to a
          HasManyThrough relationship, hence, the need to manually `select(*)`
          and to manually add the `laravel_through_key` to the second part of
          the union.
        */
        $likes = $this->likeActors()->select('actors.*');
        $shares = $this->shareActors()->select('actors.*')
            ->addSelect(DB::raw('`shares`.`target_id` as `laravel_through_key`'));

        return $likes->union($shares);  /* @phpstan-ignore-line */
    }

    public function url() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('note.show', [$this->actor, $this])
        );
    }

    public function activityUrl() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('note.activity', [$this->actor, $this])
        );
    }

    public function activityId() : Attribute
    {
        return Attribute::make(
            get: fn () : string => $this->url
        );
    }

    public function tags() : Attribute
    {
        return Attribute::make(
            get: fn (?string $value) : array => $value === null ? [] : json_decode($value),
            set: fn (?array $value) => $value !== null ? json_encode($value) : null
        );
    }

    public function attachments() : Attribute
    {
        return Attribute::make(
            get: fn (?string $value) : AnonymousResourceCollection => $this->mediaAttachments === null ? AttachmentResource::collection([]) : AttachmentResource::collection($this->mediaAttachments),
            set: fn ($value) => null
        );
    }

    public function inReplyTo() : Attribute
    {
        return Attribute::make(
            get: function (?string $value) : ?string {
                if ($value !== null) {
                    return $value;
                }

                if ($this->replyTo_id && $this->replyingTo instanceof Note) {
                    return route('note.show', [$this->replyingTo->actor, $this->replyingTo]);
                }
                return null;
            },
            set: fn ($value) => null
        );
    }

    public function getAPNote() : ActivityNote
    {
        $note = parent::getAPNote();

        // Add the interaction policy for quotes
        $note->interactionPolicy = [
            'canQuote' => [
                InteractionPolicy::AUTOMATIC->value => Context::ACTIVITY_STREAMS_PUBLIC,
            ],
        ];

        $note->replies = $this->getAPReplies();

        return $note;
    }

    /** Publications */
    public function getAPActivity() : Create
    {
        $context = [
            Context::ACTIVITY_STREAMS,
            Context::$status,
        ];

        /** @var \App\Domain\ActivityPub\Mastodon\Create $create */
        $create = Type::create('Create', [
            '@context' => $context,
            'id' => $this->activityId,
            'actor' => $this->actor->url,
            'published' => $this->published_at ? $this->published_at->toIso8601ZuluString() : null,
            'object' => $this->getAPNote()->toArray(),
            'to' => Arr::wrap($this->to),
            'cc' => Arr::wrap($this->cc),

        ]);

        return $create;
    }

    public function getAPReplies() : Collection
    {
        /** @var \ActivityPhp\Type\Core\Collection  $collection */
        $collection = Type::create('Collection', [
            'id' => route('note.replies', [$this->actor, $this]),
            'first' => Type::create('CollectionPage', [
                'next' => route('note.replies', [$this->actor, $this]),
                'partOf' => route('note.replies', [$this->actor, $this]),
                'items' => $this->directReplies()->pluck('activityId'),
            ]),
        ]);

        return $collection;
    }

    public function publish() : self
    {
        $this->published_at = now();
        $this->save();

        if ($this->wasRecentlyCreated) {
            LocalNotePublished::dispatch($this);
        } elseif ($this->isDirty(Arr::except($this->getAttributes(), 'published_at'))) {
            LocalNoteUpdated::dispatch($this);
        }

        return $this;
    }

    public function fillRecipients() : self
    {
        switch ($this->visibility) {
            case Visibility::PUBLIC:
                $this->to = [Context::ACTIVITY_STREAMS_PUBLIC];
                $this->cc = [$this->actor->followers_url];
                break;
            case Visibility::UNLISTED:
                $this->to = [$this->actor->followers_url];
                $this->cc = [Context::ACTIVITY_STREAMS_PUBLIC];
                break;
            case Visibility::PRIVATE:
                // Only followers
                $this->to = [$this->actor->followers_url];
                $this->cc = [];
                break;
            case Visibility::DIRECT:
                // Only mentioned users?
            default:
                throw new Exception('Unsupported visibility type for note');
        }

        return $this;
    }

    public function toFeedItem(): FeedItem
    {
        $title = $this->summary ?? Str::limit($this->content, 100);
        $content = $this->content;
        foreach ($this->mediaAttachments as $media) {
            $content .= '
            <a class="" href="' . $media->remote_url . '" target="_blank" rel="nofollow noopener">
                <img src="' . $media->remote_url . '" alt="' . $media->description . '">
            </a>';
        }

        $item = FeedItem::create()
            ->id($this->actor->username . '/' . $this->id)
            ->title(strip_tags($title))
            ->image($this->actor->avatar)
            ->summary($content)
            ->link($this->url)
            ->authorName($this->actor->name)
            ->authorEmail($this->actor->canonical_username);

        /** @var \App\Domain\Feed\FeedItem $item */
        $item->media($this->mediaAttachments);

        if ($this->updated_at instanceof Carbon) {
            $item->updated($this->updated_at);
        }

        return $item;
    }

    protected function getIdFromActivityId(string $activityId) : string
    {
        $matches = [];
        if (preg_match(self::NOTE_REGEX, $activityId, $matches) === 0) {
            throw new LocalIdException('ID not found in provided ActivityID: ' . $activityId);
        }
        return $matches['noteId'];  // @phpstan-ignore offsetAccess.nonOffsetAccessible ()
    }

    public function scopeByActivityId(Builder $query, string $activityId) : Builder
    {
        return $query->where('id', $this->getIdFromActivityId($activityId));
    }
}
