<?php

namespace App\Models\ActivityPub;

use ActivityPhp\Type;
use ActivityPhp\Type\Core\Collection;
use App\Domain\ActivityPub\Mastodon\Create;
use App\Domain\ActivityPub\Mastodon\Note as ActivityNote;
use App\Events\LocalNotePublished;
use App\Services\ActivityPub\Context;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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
 * @property int $actor_id
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
 * @property-read string $activity_id
 * @property-read string $activity_url
 * @property-read \App\Models\ActivityPub\LocalActor $actor
 * @property-read array $content_map
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Actor> $likeActors
 * @property-read int|null $like_actors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $likes
 * @property-read int|null $likes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Actor> $peers
 * @property-read int|null $peers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $replies
 * @property-read int|null $replies_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Actor> $shareActors
 * @property-read int|null $share_actors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shares
 * @property-read int|null $shares_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\LocalActor> $mentions
 * @property-read int|null $mentions_count
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
 * @method static Builder|LocalNote wherePublishedAt($value)
 * @method static Builder|LocalNote whereRepliesRaw($value)
 * @method static Builder|LocalNote whereSensitive($value)
 * @method static Builder|LocalNote whereSource($value)
 * @method static Builder|LocalNote whereStartTime($value)
 * @method static Builder|LocalNote whereSummary($value)
 * @method static Builder|LocalNote whereSummaryMap($value)
 * @method static Builder|LocalNote whereTags($value)
 * @method static Builder|LocalNote whereTo($value)
 * @method static Builder|LocalNote whereType($value)
 * @method static Builder|LocalNote whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class LocalNote extends Note
{
    use HasFactory;
    use HasParent;

    public const NOTE_REGEX = '#^https://(?<domain>[\w\.\_\-]+)/(?<user>[\w\.\_\-]+)/(?<noteId>\d+)$#';

    protected $fillable = ['note_type'];

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

    public function replies() : HasMany
    {
        return $this->hasMany(Note::class, 'replyTo_id');
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
            get: fn (?string $value) : array => $value === null ? [] : json_decode($value),
            set: fn (?array $value) => $value !== null ? json_encode($value) : null
        );
    }

    public function getAPNote() : ActivityNote
    {
        /** @var \App\Domain\ActivityPub\Mastodon\Note $note */
        $note = Type::create('Note', [
            'id' => $this->activityId,
            'type' => 'Note',
            // On Mastodon, if sensitive is true, only this is visible, content goes after a click
            'summary' => null,
            'inReplyTo' => null,
            'published' => $this->published_at ? $this->published_at->toIso8601ZuluString() : null,
            'url' => $this->url,
            'attributedTo' => $this->actor->url,
            'to' => [
                Context::ACTIVITY_STREAMS_PUBLIC,
            ],
            'cc' => [
                $this->actor->followers_url,
            ],
            'sensitive' => $this->sensitive,

            // "atomUri" => "https://mastodon.uy/users/j3j5/statuses/109316859449385938",
            // "conversation": "tag:hachyderm.io,2022-11-10:objectId=1050302:objectType=Conversation",
            // 'inReplyToAtomUri' => null,
            'content' => $this->content,
            // TODO: implement proper support for languages/translations
            'contentMap' => $this->contentMap,
            'attachment' => $this->attachments,
            'tag' => $this->tags,
            'replies' => $this->getAPReplies(),
        ]);

        return $note;
    }

    public function getAPCreate() : Create
    {
        $context = [
            'https://www.w3.org/ns/activitystreams',
            [
                'ostatus' => 'http://ostatus.org#',
                'atomUri' => 'ostatus:atomUri',
                'inReplyToAtomUri' => 'ostatus:inReplyToAtomUri',
                'conversation' => 'ostatus:conversation',
                'sensitive' => 'as:sensitive',
                'toot' => 'http://joinmastodon.org/ns#',
                'votersCount' => 'toot:votersCount',
                'Hashtag' => 'as:Hashtag',
            ],
        ];

        /** @var \App\Domain\ActivityPub\Mastodon\Create $create */
        $create = Type::create('Create', [
            '@context' => $context,
            'id' => $this->activityId,
            'actor' => $this->actor->url,
            'published' => $this->published_at ? $this->published_at->toIso8601ZuluString() : null,
            'to' => [
                Context::ACTIVITY_STREAMS_PUBLIC,
            ],
            'cc' => [
                $this->actor->followers_url,
            ],
            'object' => $this->getAPNote()->toArray(),
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

    public function scopeByActivityId(Builder $query, string $activityId) : Builder
    {
        return $query->where('id', $this->getIdFromActivityId($activityId));
    }

    protected function getIdFromActivityId(string $activityId) : string
    {
        $matches = [];
        if (preg_match(self::NOTE_REGEX, $activityId, $matches) === 0) {
            throw new RuntimeException('ID not found in provided ActivityID: ' . $activityId);
        }
        return $matches['noteId'];
    }

    public function scopePublished(Builder $query) : Builder
    {
        return $query->whereNotNull('published_at');
    }
}
