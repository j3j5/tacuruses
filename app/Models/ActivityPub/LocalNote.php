<?php

namespace App\Models\ActivityPub;

use ActivityPhp\Type;
use ActivityPhp\Type\Core\Collection;
use App\Domain\ActivityPub\Mastodon\Create;
use App\Domain\ActivityPub\Mastodon\Note as ActivityNote;
use App\Enums\Visibility;
use App\Events\LocalNotePublished;
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
use Illuminate\Support\Facades\DB;
use Parental\HasParent;
use RuntimeException;

use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\preg_match;

/**
 * App\Models\ActivityPub\LocalNote
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $activityId
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property string $content
 * @property string|null $contentMap
 * @property string|null $summary On Mastodon, this field contains the visible way when sensitive is true
 * @property string|null $summaryMap
 * @property bool $sensitive Mastodon-specific; content warning
 * @property array $to array of recipients
 * @property array|null $bto array of recipients of the blind carbon copy
 * @property array|null $cc array of recipients of the carbon copy
 * @property array|null $bcc array of recipients of the blind carbon copy
 * @property string|null $inReplyTo activityId of the note is replying to, if any
 * @property string|null $generator the entity that generated the object
 * @property string|null $location
 * @property \Illuminate\Support\Carbon|null $startTime
 * @property \Illuminate\Support\Carbon|null $endTime
 * @property array $attachments
 * @property array $tags
 * @property string|null $repliesRaw
 * @property array|null $source original representation of the content
 * @property string|null $conversation
 * @property string $type
 * @property int|null $replyTo_id
 * @property string $note_type
 * @property int $actor_id
 * @property Visibility $visibility
 * @property-read string $activity_id
 * @property-read string $activity_url
 * @property-read \App\Models\ActivityPub\LocalActor $actor
 * @property-read array $content_map
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $directReplies
 * @property-read int|null $direct_replies_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Actor> $likeActors
 * @property-read int|null $like_actors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $likes
 * @property-read int|null $likes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Actor> $peers
 * @property-read int|null $peers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Actor> $shareActors
 * @property-read int|null $share_actors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shares
 * @property-read int|null $shares_count
 * @property-read string $url
 * @method static Builder|LocalNote byActivityId(string $activityId)
 * @method static Builder|LocalNote newModelQuery()
 * @method static Builder|LocalNote newQuery()
 * @method static Builder|LocalNote published()
 * @method static Builder|LocalNote query()
 * @method static Builder|LocalNote whereActivityId($value)
 * @method static Builder|LocalNote whereActorId($value)
 * @method static Builder|LocalNote whereAttachments($value)
 * @method static Builder|LocalNote whereBcc($value)
 * @method static Builder|LocalNote whereBto($value)
 * @method static Builder|LocalNote whereCc($value)
 * @method static Builder|LocalNote whereContent($value)
 * @method static Builder|LocalNote whereContentMap($value)
 * @method static Builder|LocalNote whereConversation($value)
 * @method static Builder|LocalNote whereCreatedAt($value)
 * @method static Builder|LocalNote whereEndTime($value)
 * @method static Builder|LocalNote whereGenerator($value)
 * @method static Builder|LocalNote whereId($value)
 * @method static Builder|LocalNote whereInReplyTo($value)
 * @method static Builder|LocalNote whereLocation($value)
 * @method static Builder|LocalNote whereNoteType($value)
 * @method static Builder|LocalNote wherePublishedAt($value)
 * @method static Builder|LocalNote whereRepliesRaw($value)
 * @method static Builder|LocalNote whereReplyToId($value)
 * @method static Builder|LocalNote whereSensitive($value)
 * @method static Builder|LocalNote whereSource($value)
 * @method static Builder|LocalNote whereStartTime($value)
 * @method static Builder|LocalNote whereSummary($value)
 * @method static Builder|LocalNote whereSummaryMap($value)
 * @method static Builder|LocalNote whereTags($value)
 * @method static Builder|LocalNote whereTo($value)
 * @method static Builder|LocalNote whereType($value)
 * @method static Builder|LocalNote whereUpdatedAt($value)
 * @method static Builder|LocalNote whereVisibility($value)
 * @mixin \Eloquent
 */
class LocalNote extends Note
{
    use HasFactory;
    use HasParent;

    public const NOTE_REGEX = '#^https://(?<domain>[\w\.\_\-]+)/(?<user>[\w\.\_\-]+)/(?<noteId>\d+)$#';

    protected $fillable = ['type', 'note_type'];

    /** @var array<string, string> */
    protected $casts = [
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
        // 'attachments' => 'array',
        // 'tags' => 'array',
    ];

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

    public function directReplies() : HasMany
    {
        return $this->hasMany(Note::class, 'replyTo_id');
    }

    public function mediaAttachments() : HasMany
    {
        return $this->hasMany(Media::class);
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
          There seem to be something weird when applying an union to a
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

    public function contentMap() : Attribute
    {
        return Attribute::make(
            get: fn (?string $value) : array => $value === null
                ? [$this->actor->language => $this->content]
                : json_decode($value),
            set: fn (?array $value) => $value !== null
                ? json_encode($value)
                : null
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

    public function getAPNote() : ActivityNote
    {
        $note = parent::getAPNote();
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
                'items' => [],
            ]),
        ]);

        return $collection;
    }

    public function publish() : self
    {
        $this->published_at = now();
        $this->save();

        LocalNotePublished::dispatch($this);

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

    protected function getIdFromActivityId(string $activityId) : string
    {
        $matches = [];
        if (preg_match(self::NOTE_REGEX, $activityId, $matches) === 0) {
            throw new RuntimeException('ID not found in provided ActivityID: ' . $activityId);
        }
        return $matches['noteId'];
    }

    public function scopeByActivityId(Builder $query, string $activityId) : Builder
    {
        return $query->where('id', $this->getIdFromActivityId($activityId));
    }

    public function scopePublished(Builder $query) : Builder
    {
        return $query->whereNotNull('published_at');
    }
}
