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
use Parental\HasParent;
use RuntimeException;

use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\preg_match;

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
