<?php

namespace App\Models\ActivityPub;

use ActivityPhp\Type;
use ActivityPhp\Type\Extended\Object\Note as ObjectNote;
use App\Services\ActivityPub\Context;
use App\Traits\HasSnowflakePrimary;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $likes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shares
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $likes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shares
 * @mixin \Eloquent
 */
class Note extends Model
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
        // There seem to be something weird when applying an union to a HasManyThrough,
        // hence, the need to manually `select(*)` and to manually add the `laravel_through_key`
        // to the second part of the union
        $likes = $this->likeActors()->select('*');
        $shares = $this->shareActors()->select('*')
            ->addSelect(DB::raw('`shares`.`target_id` as `laravel_through_key`'));

        return $likes->union($shares);
        // return $likes->getQuery()->union($shares->getQuery());
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
            'id' => $this->activityId,
            'type' => 'Note',
            'summary' => null,
            'inReplyTo' => null,
            'published' => $this->created_at ? $this->created_at->toIso8601ZuluString() : null,
            'url' => $this->url,
            'attributedTo' => $this->actor->getProfileUrl(),
            'to' => [
                Context::ACTIVITY_STREAMS_PUBLIC,
            ],
            'cc' => [
                $this->actor->getFollowersUrl(),
            ],
            'sensitive' => $this->isSensitive(),

            // "atomUri" => "https://mastodon.uy/users/j3j5/statuses/109316859449385938",
            // "conversation": "tag:hachyderm.io,2022-11-10:objectId=1050302:objectType=Conversation",
            'inReplyToAtomUri' => null,
            'content' => $this->text,
            // TODO: implement proper support for languages/translations
            'contentMap' => [
                $this->language => $this->text,
            ],
            'attachment' => $this->attachments,
            'tag' => $this->tags,
            'replies' => $this->replies,
        ]);

        return $note;
    }

    public function getActivityUrl() : string
    {
        return $this->activity_url;
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
