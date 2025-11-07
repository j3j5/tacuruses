<?php

declare(strict_types=1);

namespace App\Models\ActivityPub;

use ActivityPhp\Type;
use App\Domain\ActivityPub\Mastodon\QuoteAuthorization;
use App\Services\ActivityPub\Context;
use App\Traits\HasSnowflakePrimary;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vinkla\Hashids\Facades\Hashids;

/**
 * @property int $id
 * @property int $actor_id
 * @property int $target_id
 * @property string $activityId
 * @property array<array-key, mixed> $quote
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ActivityPub\Actor $actor
 * @property-read string $authorization_url
 * @property-read string $slug
 * @property-read \App\Models\ActivityPub\LocalNote $target
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote byActivityId(string $activityId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote whereQuote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Quote extends Model
{
    use HasSnowflakePrimary;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quote' => 'array',
        ];
    }

    public function actor() : BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }

    public function target() : BelongsTo
    {
        return $this->belongsTo(LocalNote::class, 'target_id');
    }

    public function slug() : Attribute
    {
        return Attribute::make(
            get: fn () : string => Hashids::encode($this->id)
        );
    }

    public function authorizationUrl() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('actor.approved-quotes', [$this->actor, $this])
        );
    }

    public function getApObject(): QuoteAuthorization
    {
        /** @var \App\Domain\ActivityPub\Mastodon\QuoteAuthorization $note */
        $note = Type::create('QuoteAuthorization', [
            '@context' => [
                Context::ACTIVITY_STREAMS,
                Context::$quoteAuth,
            ],
            'id' => $this->activityId,
            'attributedTo' => $this->target->actor->activity_id,
            'interactingObject' => $this->quote['id'],
            'interactionTarget' => $this->target->activity_id,
        ]);

        return $note;
    }

    protected function scopeByActivityId(Builder $query, string $activityId): void
    {
        $query->where('activityId', $activityId);
    }
}
