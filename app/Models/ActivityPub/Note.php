<?php

namespace App\Models\ActivityPub;

use ActivityPhp\Type;
use ActivityPhp\Type\Extended\Object\Note as ObjectNote;
use App\Domain\ActivityPub\Contracts\Actor;
use App\Domain\ActivityPub\Contracts\Note as ContractsNote;
use App\Services\ActivityPub\Context;
use App\Traits\HasSnowflakePrimary;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\preg_match;

/**
 * App\Models\ActivityPub\Note
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $actor_id
 * @property int $sensitive
 * @property string $text
 * @property string|null $summary
 * @property string|null $inReplyTo activityId of the status is replying to
 * @property string $language
 * @property array $attachments
 * @property array $tags
 * @property-read string $activity_id
 * @property-read string $activityId
 * @property-read string $activity_url
 * @property-read \App\Models\ActivityPub\LocalActor $actor
 * @property-read array $replies
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
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $likes
 * @property-read int|null $likes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shares
 * @property-read int|null $shares_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $likes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shares
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $likes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shares
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $likes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shares
 * @mixin \Eloquent
 */
class Note extends Model implements ContractsNote
{
    use HasFactory;
    use HasSnowflakePrimary;

    public const NOTE_REGEX = '#^https://(?<domain>[\w\.\_\-]+)/(?<user>[\w\.\_\-]+)/(?<noteId>\d+)$#';

    /** @var array<string, string> */
    protected $casts = [
        'sensitive' => 'boolean',
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

    public function url() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('status.show', [$this->actor, $this])
        );
    }

    public function activityUrl() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('status.activity', [$this->actor, $this])
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
            get: fn (?string $value) : array => $value === null ? [] : json_decode($value),
            set: fn (?array $value) => $value !== null ? json_encode($value) : null
        );
    }

    public function replies() : Attribute
    {
        return Attribute::make(
            get: fn () : array => []
        );
    }

    public function getAPNote() : ObjectNote
    {
        /** @var \ActivityPhp\Type\Extended\Object\Note $note */
        $note = Type::create('Note', [
            'id' => $this->getNoteUrl(),
            'summary' => null,
            'inReplyTo' => null,
            'published' => $this->getPublishedStatusAt()->toIso8601ZuluString(),
            'url' => $this->getNoteUrl(),
            'attributedTo' => self::getActor()->activityId,
            'to' => [
                Context::ACTIVITY_STREAMS_PUBLIC,
            ],
            'cc' => [
                $this->getActor()->getFollowersUrl(),
            ],
            'sensitive' => $this->isSensitive(),
            'content' => $this->getText(),
            'contentMap' => [
                'es' => $this->getText(),
            ],
            'attachment' => $this->getAttachment(),
            'tag' => $this->getTags(),
            'replies' => $this->getReplies(),
        ]);

        return $note;
    }

    public function getText() : string
    {
        return $this->text;
    }

    public function getLanguage() : string
    {
        return $this->language;
    }

    public function getPublishedStatusAt() : Carbon
    {
        /** @phpstan-ignore-next-line */
        return $this->created_at;
    }

    public function getNoteUrl() : string
    {
        return $this->url;
    }

    public function getActivityUrl() : string
    {
        return $this->activity_url;
    }

    public function getActor() : Actor
    {
        return $this->actor;
    }

    public function getAttachment() : array
    {
        return $this->attachments;
    }

    public function getTags() : array
    {
        return $this->tags;
    }

    public function getReplies() : array
    {
        return $this->replies;
    }

    public function isSensitive() : bool
    {
        return (bool) $this->sensitive;
    }

    public function scopeByActivityId(Builder $query, string $activityId) : void
    {
        $matches = [];
        if (preg_match(self::NOTE_REGEX, $activityId, $matches) === 0) {
            throw new RuntimeException('ID not found in provided ActivityID: ' . $activityId);
        }
        $query->where('id', $matches['noteId']);
    }
}
