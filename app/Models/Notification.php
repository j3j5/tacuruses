<?php

namespace App\Models;

use App\Enums\NotificationTypes;
use App\Models\ActivityPub\LocalActor;
use App\Traits\HasSnowflakePrimary;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\DatabaseNotification;

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
 * @mixin \Eloquent
 */
class Notification extends DatabaseNotification
{
    use HasFactory;
    use HasSnowflakePrimary;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'type' => NotificationTypes::class,
    ];

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
}
