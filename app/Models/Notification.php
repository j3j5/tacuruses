<?php

declare(strict_types = 1);

namespace App\Models;

use App\Domain\Feed\FeedItem;
use App\Enums\NotificationTypes;
use App\Models\ActivityPub\Activity;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\LocalActor;
use App\Traits\HasSnowflakePrimary;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Spatie\Feed\Feedable;

/**
 *
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property NotificationTypes $type
 * @property int $actor_id
 * @property int $from_actor_id
 * @property int $activity_id
 * @property-read LocalActor $actor
 * @property-read string $text
 * @method static \Illuminate\Database\Eloquent\Builder|Notification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Notification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Notification query()
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereFromActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereUpdatedAt($value)
 * @property string $notifiable_type
 * @property int $notifiable_id
 * @property array $data
 * @property-read Activity|null $activity
 * @property-read Actor|null $fromActor
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $notifiable
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection<int, static> all($columns = ['*'])
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection<int, static> get($columns = ['*'])
 * @method static Builder|DatabaseNotification read()
 * @method static Builder|DatabaseNotification unread()
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereNotifiableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereNotifiableType($value)
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection<int, static> all($columns = ['*'])
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection<int, static> get($columns = ['*'])
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection<int, static> all($columns = ['*'])
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection<int, static> get($columns = ['*'])
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection<int, static> all($columns = ['*'])
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection<int, static> get($columns = ['*'])
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
