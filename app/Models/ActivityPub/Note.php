<?php

namespace App\Models\ActivityPub;

use ActivityPhp\Type;
use App\Domain\ActivityPub\Mastodon\Note as ActivityNote;
use App\Enums\Visibility;
use App\Services\ActivityPub\Context;
use App\Traits\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Parental\HasChildren;
use function Safe\json_decode;

use function Safe\json_encode;
use Stevebauman\Purify\Facades\Purify;

/**
 * App\Models\ActivityPub\Note
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $activityId
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property string $content
 * @property array|null $contentMap
 * @property string|null $summary On Mastodon, this field contains the visible way when sensitive is true
 * @property array|null $summaryMap
 * @property bool $sensitive Mastodon-specific; content warning
 * @property string $to array of recipients
 * @property string|null $bto array of recipients of the blind carbon copy
 * @property string|null $cc array of recipients of the carbon copy
 * @property string|null $bcc array of recipients of the blind carbon copy
 * @property string|null $inReplyTo activityId of the note is replying to, if any
 * @property string|null $generator the entity that generated the object
 * @property string|null $location
 * @property string|null $startTime
 * @property string|null $endTime
 * @property array $attachments
 * @property array $tags
 * @property string|null $repliesRaw
 * @property string|null $source original representation of the content
 * @property string|null $conversation
 * @property string $type
 * @property int|null $replyTo_id
 * @property string $note_type
 * @property int $actor_id
 * @property Visibility $visibility
 * @property-read string $activity_url
 * @property-read \App\Models\ActivityPub\Actor $actor
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\LocalActor> $mentions
 * @property-read int|null $mentions_count
 * @property-read string $url
 * @method static \Illuminate\Database\Eloquent\Builder|Note newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Note newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Note query()
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereBcc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereBto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereCc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereContentMap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereConversation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereGenerator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereInReplyTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereNoteType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereRepliesRaw($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereReplyToId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereSensitive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereSummaryMap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereVisibility($value)
 * @property string|null $original_content
 * @property-read array $content_map
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereOriginalContent($value)
 * @mixin \Eloquent
 */
class Note extends Model
{
    use HasFactory;
    use HasChildren;
    use HasSnowflakePrimary;
    use SoftDeletes;

    protected $fillable = ['type', 'note_type'];

    /** @var array<string, class-string> */
    protected array $childTypes = [
        'local' => LocalNote::class,
        'remote' => RemoteNote::class,
    ];

    protected string $childColumn = 'note_type';

    /** @var array<string, string> */
    protected $casts = [
        'id' => 'integer',
        'sensitive' => 'boolean',
        'published_at' => 'datetime',
        'visibility' => Visibility::class,
        'summaryMap' => 'array',
        // Implemented manually to force array return
        // 'contentMap' => 'array',
        // 'attachments' => 'array',
        // 'tags' => 'array',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (LocalNote $note) {
            // Use soft delete but keep the content
            $note->content = 'DELETED';
            $note->contentMap = [];
            $note->original_content = null;
            $note->save();

            return $note;
        });
    }

    public function actor() : BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }

    public function mentions() : BelongsToMany
    {
        return $this->belongsToMany(LocalActor::class);
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

    public function content() : Attribute
    {
        return Attribute::make(
            get: function (?string $value): string {
                if ($value !== null) {
                    /** @phpstan-ignore-next-line */
                    return Purify::clean($value);
                }

                if(!is_array($this->contentMap) || count($this->contentMap) === 0) {
                    return '';
                }

                if (count($this->contentMap) === 1) {
                    $content = Arr::first($this->contentMap);
                } else {
                    $content = Arr::get(
                        $this->contentMap,
                        $this->actor->language,
                        Arr::first($this->contentMap)
                    );
                }

                // TODO: add support for other content-types like, markdown...
                /** @phpstan-ignore-next-line */
                return Purify::clean($content);
            }
        );
    }

    public function contentMap() : Attribute
    {
        return Attribute::make(
            get: fn (?string $value) : array => $value === null
                ? [$this->actor->language => $this->content]
                : json_decode($value, true),
            set: fn (?array $value) => $value !== null
                ? json_encode($value)
                : null
        );
    }

    public function getAPNote() : ActivityNote
    {
        /** @var \App\Domain\ActivityPub\Mastodon\Note $note */
        $note = Type::create('Note', [
            '@context' => [
                Context::ACTIVITY_STREAMS,
                Context::$status,
            ],
            'id' => $this->activityId,
            'type' => 'Note',
            // On Mastodon, if sensitive is true, only this is visible, content goes after a click
            'summary' => $this->summary,
            // TODO: implement
            'inReplyTo' => $this->inReplyTo,
            'published' => $this->published_at ? $this->published_at->toIso8601ZuluString() : null,
            'url' => $this->url,
            'attributedTo' => $this->actor->url,
            'to' => Arr::wrap($this->to),
            'cc' => Arr::wrap($this->cc),
            'sensitive' => $this->sensitive,

            // "atomUri" => "https://mastodon.uy/users/j3j5/statuses/109316859449385938",
            // "conversation": "tag:hachyderm.io,2022-11-10:objectId=1050302:objectType=Conversation",
            // 'inReplyToAtomUri' => null,
            'content' => $this->content,
            // TODO: implement proper support for languages/translations
            'contentMap' => $this->contentMap,
            'attachment' => $this->attachments,
            'tag' => $this->tags,
            // 'replies' => $this->getAPReplies(),
        ]);

        return $note;
    }
}
