<?php

declare(strict_types = 1);

namespace App\Models;

use App\Domain\Feed\FeedItem;
use App\Enums\NotificationTypes;
use App\Models\ActivityPub\Activity;
use App\Models\ActivityPub\Actor;
use App\Traits\HasSnowflakePrimary;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Feed\Feedable;

/**
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $read_at
 * @property NotificationTypes $type
 * @property string $notifiable_type
 * @property int $notifiable_id
 * @property array<array-key, mixed> $data
 * @property int|null $from_actor_id The actor "generating" the notification
 * @property int|null $activity_id The activity that generated the notification
 * @property-read Activity|null $activity
 * @property-read Actor|null $fromActor
 * @property-read \Illuminate\Database\Eloquent\Model $notifiable
 * @property-read string $text
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection<int, static> all($columns = ['*'])
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection<int, static> get($columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification query()
 * @method static Builder<static>|Notification read()
 * @method static Builder<static>|Notification unread()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereFromActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereNotifiableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereNotifiableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Notification extends DatabaseNotification implements Feedable
{
    use HasFactory;
    use HasSnowflakePrimary;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'type' => NotificationTypes::class,
        ];
    }

    public function activity() : BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function fromActor() : BelongsTo
    {
        return $this->belongsTo(Actor::class, 'from_actor_id');
    }

    public function text() : Attribute
    {
        return Attribute::make(
            get: function () : string {
                $text = data_get($this->data, 'key', '');
                $replace = data_get($this->data, 'replace', []);
                $locale = app()->getLocale();
                if ($this->notifiable instanceof HasLocalePreference) {
                    $locale = $this->notifiable->preferredLocale();
                }

                return trans($text, $replace, $locale);
            }
        );
    }

    public function toFeedItem(): FeedItem
    {
        $title = Str::limit($this->text, 100);
        $content = $this->text;

        $item = FeedItem::create()
            ->id('Notification ' . $this->id)
            ->title(strip_tags($title))
            ->image(data_get($this->fromActor, 'avatar', ''))
            ->summary($content)
            ->link(data_get($this->activity, 'activityId', ''))
            ->authorName(data_get($this->fromActor, 'name', ''))
            ->authorEmail(data_get($this->fromActor, 'canonical_username', ''));

        /** @var \App\Domain\Feed\FeedItem $item */
        $item->media(collect());

        if ($this->updated_at instanceof Carbon) {
            $item->updated($this->updated_at);
        }

        return $item;
    }
}
