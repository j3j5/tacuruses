<?php

namespace App\Models\ActivityPub;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;
use RuntimeException;

use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\preg_match;

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
        // Implemented manually to force array return
        // 'attachments' => 'array',
        // 'tags' => 'array',
    ];

    public function actor() : BelongsTo
    {
        return $this->belongsTo(RemoteActor::class, 'actor_id');
    }

    // public function url() : Attribute
    // {
    //     return Attribute::make(
    //         get: fn () : string => route('status.show', [$this->actor, $this])
    //     );
    // }

    // public function activityUrl() : Attribute
    // {
    //     return Attribute::make(
    //         get: fn () : string => route('status.activity', [$this->actor, $this])
    //     );
    // }

    // public function activityId() : Attribute
    // {
    //     return Attribute::make(
    //         get: fn () : string => $this->url
    //     );
    // }

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

    public function getActivityUrl() : string
    {
        return $this->activity_url;
    }

    // public function scopeByActivityId(Builder $query, string $activityId) : void
    // {
    //     $matches = [];
    //     if (preg_match(self::NOTE_REGEX, $activityId, $matches) === 0) {
    //         throw new RuntimeException('ID not found in provided ActivityID: ' . $activityId);
    //     }
    //     $query->where('id', $matches['noteId']);
    // }
}
