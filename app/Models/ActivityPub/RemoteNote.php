<?php

namespace App\Models\ActivityPub;

use ActivityPhp\Type;
use App\Domain\ActivityPub\Mastodon\Note as ActivityNote;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;

use function Safe\json_decode;
use function Safe\json_encode;

/**
 * App\Models\ActivityPub\RemoteNote
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $actor_id
 * @property string|null $activityId
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property string $content
 * @property array|null $contentMap
 * @property string|null $summary On Mastodon, this field contains the visible way when sensitive is true
 * @property array|null $summaryMap
 * @property bool $sensitive Mastodon-specific; content warning
 * @property array $to array of recipients
 * @property string|null $bto array of recipients of the blind carbon copy
 * @property array|null $cc array of recipients of the carbon copy
 * @property string|null $bcc array of recipients of the blind carbon copy
 * @property string|null $inReplyTo activityId of the note is replying to, if any
 * @property string|null $generator the entity that generated the object
 * @property string|null $location
 * @property \Illuminate\Support\Carbon|null $startTime
 * @property \Illuminate\Support\Carbon|null $endTime
 * @property array $attachments
 * @property array $tags
 * @property array|null $repliesRaw
 * @property string|null $source original representation of the content
 * @property string|null $conversation
 * @property string $type
 * @property-read string $activity_id
 * @property-read string $activity_url
 * @property-read \App\Models\ActivityPub\RemoteActor $actor
 * @property-read string $url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\LocalActor> $mentions
 * @property-read int|null $mentions_count
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote byActivityId(string $activityId)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote query()
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereBcc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereBto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereCc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereContentMap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereConversation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereGenerator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereInReplyTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereRepliesRaw($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereSensitive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereSummaryMap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RemoteNote extends Note
{
    use HasFactory;
    use HasParent;

    /** @var array<string, string> */
    protected $casts = [
        'sensitive' => 'boolean',
        'to' => 'array',
        'cc' => 'array',
        'contentMap' => 'array',
        'summaryMap' => 'array',
        'repliesRaw' => 'array',
        'published_at' => 'datetime',
        'startTime' => 'datetime',
        'endTime' => 'datetime',
        // Implemented manually to force array return
        // 'attachments' => 'array',
        // 'tags' => 'array',
    ];

    protected $fillable = ['note_type', 'activityId'];

    public function actor() : BelongsTo
    {
        return $this->belongsTo(RemoteActor::class, 'actor_id');
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
            'summary' => $this->summary,
            'inReplyTo' => $this->inReplyTo,
            'published' => $this->published_at ? $this->published_at->toIso8601ZuluString() : null,
            'url' => $this->url,
            'attributedTo' => $this->actor->url,
            'to' => $this->to,
            'cc' => $this->cc,
            'sensitive' => $this->sensitive,

            // "atomUri" => "https://mastodon.uy/users/j3j5/statuses/109316859449385938",
            // "conversation": "tag:hachyderm.io,2022-11-10:objectId=1050302:objectType=Conversation",
            // 'inReplyToAtomUri' => null,
            'content' => $this->content,
            // TODO: implement proper support for languages/translations
            'contentMap' => $this->contentMap,
            'attachment' => $this->attachments,
            'tag' => $this->tags,
            'replies' => $this->repliesRaw,
        ]);

        return $note;
    }

    public function url() : Attribute
    {
        return Attribute::make(
            get: fn () : string => (string) $this->activityId
        );
    }

    public function scopeByActivityId(Builder $query, string $activityId) : Builder
    {
        return $query->where('activityId', $activityId);
    }
}
