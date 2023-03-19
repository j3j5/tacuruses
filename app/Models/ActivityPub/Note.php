<?php

namespace App\Models\ActivityPub;

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
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Note whereUpdatedAt($value)
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

    protected string $childColumn = 'type';

    /** @var array<string, string> */
    protected $casts = [
        'sensitive' => 'boolean',
        // Implemented manually to force array return
        // 'attachments' => 'array',
        // 'tags' => 'array',
    ];

    public function actor() : BelongsTo
    {
        return $this->belongsTo(Actor::class, 'actor_id');
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

    public function mentions() : BelongsToMany
    {
        return $this->belongsToMany(LocalActor::class);
    }

}
