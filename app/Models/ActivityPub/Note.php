<?php

namespace App\Models\ActivityPub;

use ActivityPhp\Type;
use ActivityPhp\Type\Extended\Object\Note as ObjectNote;
use App\Domain\ActivityPub\Contracts\Actor;
use App\Domain\ActivityPub\Contracts\Note as ContractsNote;
use App\Services\ActivityPub\Context;
use App\Traits\HasSnowflakePrimary;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use function Safe\json_decode;

class Note extends Model implements ContractsNote
{
    use HasFactory;
    use HasSnowflakePrimary;

    public function actor() : BelongsTo
    {
        return $this->belongsTo(LocalActor::class, 'actor_id');
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
        return Type::create('Note', [
            'id' => $this->getNoteUrl(),
            'summary' => null,
            'inReplyTo' => null,
            'published' => $this->getPublishedStatusAt()->toIso8601ZuluString(),
            'url' => $this->getNoteUrl(),
            'attributedTo' => self::getActor()->profileUrl,
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
        return $this->attachment;
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
        return $this->sensitive;
    }
}
