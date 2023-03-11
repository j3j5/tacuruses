<?php

namespace App\Models\ActivityPub;

use ActivityPhp\Type;
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
 * @property-read \App\Models\ActivityPub\RemoteActor $actor
 * @property-read array $replies
 * @property-read string $url
 * @method static \Illuminate\Database\Eloquent\Builder|Note byActivityId(string $activityId)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote query()
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereInReplyTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereSensitive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteNote whereText($value)
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
        'startTime' => 'datetime',
        'endTime' => 'datetime',
        // Implemented manually to force array return
        // 'attachments' => 'array',
        // 'tags' => 'array',
    ];

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

    public function replies() : Attribute
    {
        return Attribute::make(
            get: fn () : ?array => $this->repliesRaw === null ? null : json_decode($this->repliesRaw, true),
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
            'attributedTo' => $this->actor->profile_url,
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
            'replies' => $this->repliesRaw,
        ]);

        return $note;
    }

    public function scopeByActivityId(Builder $query, string $activityId) : void
    {
        $query->where('activityId', $activityId);
    }
}
