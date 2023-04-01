<?php

namespace App\Models\ActivityPub;

use ActivityPhp\Type;
use App\Domain\ActivityPub\Mastodon\Note as ActivityNote;
use App\Services\ActivityPub\Context;
use App\Traits\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Parental\HasChildren;

use function Safe\json_decode;
use function Safe\json_encode;

/**
 * App\Models\ActivityPub\Note
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $actor_id
 * @property bool $sensitive
 * @property string $text
 * @property string|null $summary
 * @property string|null $inReplyTo activityId of the status is replying to
 * @property string $language
 * @property array $attachments
 * @property array $tags
 * @property string $type
 * @property-read string $activity_id
 * @property-read string $activity_url
 * @property-read \App\Models\ActivityPub\Actor $actor
 * @property-read string $url
 * @method static \Illuminate\Database\Eloquent\Builder|Note byActivityId(string $activityId)
 * @method static \Illuminate\Database\Eloquent\Builder|Note newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Note newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Note query()
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereInReplyTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereSensitive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereUpdatedAt($value)
 * @property string|null $activityId
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property string $content
 * @property string|null $contentMap
 * @property string|null $summaryMap
 * @property string $to array of recipients
 * @property string|null $bto array of recipients of the blind carbon copy
 * @property string|null $cc array of recipients of the carbon copy
 * @property string|null $bcc array of recipients of the blind carbon copy
 * @property string|null $generator the entity that generated the object
 * @property string|null $location
 * @property string|null $startTime
 * @property string|null $endTime
 * @property string|null $repliesRaw
 * @property string|null $source original representation of the content
 * @property string|null $conversation
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereBcc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereBto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereCc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereContentMap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereConversation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereGenerator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereRepliesRaw($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereSummaryMap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereTo($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\LocalActor> $mentions
 * @property-read int|null $mentions_count
 * @property int|null $replyTo_id
 * @property string $note_type
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\LocalActor> $mentions
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereNoteType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereReplyToId($value)
 * @mixin \Eloquent
 */
class Note extends Model
{
    use HasFactory;
    use HasChildren;
    use HasSnowflakePrimary;

    protected $fillable = ['type', 'note_type'];

    /** @var array<string, class-string> */
    protected array $childTypes = [
        'local' => LocalNote::class,
        'remote' => RemoteNote::class,
    ];

    protected string $childColumn = 'note_type';

    /** @var array<string, string> */
    protected $casts = [
        'sensitive' => 'boolean',
        'published_at' => 'datetime',
        // Implemented manually to force array return
        // 'attachments' => 'array',
        // 'tags' => 'array',
    ];

    public function actor() : BelongsTo
    {
        return $this->belongsTo(Actor::class, 'actor_id');
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

    public function getAPNote() : ActivityNote
    {
        /** @var \App\Domain\ActivityPub\Mastodon\Note $note */
        $note = Type::create('Note', [
            'id' => $this->activityId,
            'type' => 'Note',
            // On Mastodon, if sensitive is true, only this is visible, content goes after a click
            'summary' => null,
            // TODO: implement
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
            // 'replies' => $this->getAPReplies(),
        ]);

        return $note;
    }
}
