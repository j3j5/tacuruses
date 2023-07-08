<?php

namespace App\Models\ActivityPub;

use ActivityPhp\Type;
use ActivityPhp\Type\Extended\Activity\Announce;
use App\Domain\ActivityPub\Mastodon\Note as ActivityNote;
use App\Enums\Visibility;
use App\Services\ActivityPub\Context;
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
 * @property \Illuminate\Support\Collection|null $repliesRaw
 * @property string|null $source original representation of the content
 * @property string|null $conversation
 * @property string $type
 * @property int|null $replyTo_id
 * @property string $note_type
 * @property int $actor_id
 * @property Visibility $visibility
 * @property-read string $activity_url
 * @property-read \App\Models\ActivityPub\RemoteActor $actor
 * @property-read string $url
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
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereNoteType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereRepliesRaw($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereReplyToId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereSensitive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereSummaryMap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereVisibility($value)
 * @property-read array $content_map
 * @mixin \Eloquent
 */
class RemoteNote extends Note
{
    use HasFactory;
    use HasParent;

    /** @var array<string, string> */
    protected $casts = [
        'id' => 'integer',
        'sensitive' => 'boolean',
        'to' => 'array',
        'cc' => 'array',
        'summaryMap' => 'array',
        'repliesRaw' => 'collection',
        'published_at' => 'datetime',
        'startTime' => 'datetime',
        'endTime' => 'datetime',
        'visibility' => Visibility::class,
        // 'contentMap' => 'array',
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
        $note = parent::getAPNote();
        $note->replies = $this->repliesRaw;

        return $note;
    }

    /** Shares */
    public function getAPActivity() : Announce
    {
        /** @var \ActivityPhp\Type\Extended\Activity\Announce $create */
        $create = Type::create('Announce', [
            'id' => $this->activityId . '/activity',
            '@context' => [
                Context::ACTIVITY_STREAMS,
                Context::$status,
            ],
            'actor' => $this->actor->url,
            'published' => $this->published_at ? $this->published_at->toIso8601ZuluString() : null,
            'to' => [
                Context::ACTIVITY_STREAMS_PUBLIC,
            ],
            'cc' => [
                $this->actor->activityId,
                $this->actor->followers_url,
            ],
            'object' => $this->activityId,
        ]);

        return $create;
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
