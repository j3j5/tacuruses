<?php

declare(strict_types = 1);

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
 * @property int $actor_id
 * @property string|null $replyTo_id id (PK) of the note is replying to, if any
 * @property string|null $activityId
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property string|null $original_content
 * @property string $content
 * @property string|null $contentMap
 * @property string|null $summary On Mastodon, this field contains the visible way when sensitive is true
 * @property array<array-key, mixed>|null $summaryMap
 * @property string $type Type of object, Note, Article...
 * @property bool $sensitive Mastodon-specific; content warning
 * @property array<array-key, mixed> $to array of recipients
 * @property array<array-key, mixed>|null $bto array of recipients of the blind carbon copy
 * @property array<array-key, mixed>|null $cc array of recipients of the carbon copy
 * @property array<array-key, mixed>|null $bcc array of recipients of the blind carbon copy
 * @property string|null $inReplyTo activityId of the note is replying to, if any
 * @property string|null $generator the entity that generated the object
 * @property string|null $location
 * @property \Illuminate\Support\Carbon|null $startTime
 * @property \Illuminate\Support\Carbon|null $endTime
 * @property Visibility $visibility visibility of the note, check enum Visibility
 * @property array $attachments
 * @property array $tags
 * @property \Illuminate\Support\Collection<array-key, mixed>|null $repliesRaw
 * @property string|null $source original representation of the content
 * @property string|null $conversation
 * @property string $note_type
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\ActivityPub\Activity|null $activity
 * @property-read string $activity_url
 * @property-read \App\Models\ActivityPub\RemoteActor $actor
 * @property array $content_map
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $directReplies
 * @property-read int|null $direct_replies_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Media> $mediaAttachments
 * @property-read int|null $media_attachments_count
 * @property-read \App\Models\ActivityPub\Note|null $replyingTo
 * @property-read string $url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote byActivityId(string $activityId)
 * @method static \Database\Factories\ActivityPub\RemoteNoteFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereBcc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereBto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereCc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereContentMap($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereConversation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereGenerator($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereInReplyTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereNoteType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereOriginalContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereRepliesRaw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereReplyToId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereSensitive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereSummaryMap($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote whereVisibility($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteNote withoutTrashed()
 * @mixin \Eloquent
 */
class RemoteNote extends Note
{
    use HasFactory;
    use HasParent;

    protected $fillable = ['note_type', 'activityId'];

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
            'to' => 'array',
            'bto' => 'array',
            'cc' => 'array',
            'bcc' => 'array',
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
    }

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
        $replies = Type::create('Collection', []);
        if ($this->repliesRaw !== null) {
            $replies = Type::create('Collection', $this->repliesRaw->toArray());
        }
        /** @var \ActivityPhp\Type\Core\Collection $replies */
        $note->replies = $replies;

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
