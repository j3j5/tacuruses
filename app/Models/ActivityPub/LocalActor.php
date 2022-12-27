<?php

namespace App\Models\ActivityPub;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * App\Models\ActivityPub\User
 *
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor query()
 * @mixin \Eloquent
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property string $username
 * @property string|null $avatar
 * @property string|null $header
 * @property string|null $bio
 * @property string|null $alsoKnownAs
 * @property string $publicKey
 * @property string $privateKeyPath
 * @property string|null $properties
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereAlsoKnownAs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereHeader($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor wherePrivateKeyPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor wherePublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereUsername($value)
 * @property string $model
 * @property-read string $activity_id
 * @property-read string $key_id
 * @property-read string $private_key
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereModel($value)
 * @property-read string $followers_url
 * @property-read string $following_url
 * @property-read string $inbox_url
 * @property-read string $outbox_url
 * @property-read string $profile_url
 * @property-read string $public_key
 */
class LocalActor extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $casts = [
        'alsoKnownAs' => 'array',
        'properties' => 'array',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'username';
    }

    public function activityId() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('user.show', [$this]),
        );
    }

    public function keyId() : Attribute
    {
        return Attribute::make(
            get: fn () : string => $this->activityId . '#main-key',
        );
    }

    public function publicKey() : Attribute
    {
        return Attribute::make(
            get: fn () : string => Storage::disk('local')->get("keys/local/{$this->id}/public.pem"),
        );
    }

    public function privateKey() : Attribute
    {
        return Attribute::make(
            get: fn () : string => Storage::disk('local')->get("keys/local/{$this->id}/private.pem"),
        );
    }

    public function profileUrl() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('user.show', [$this]),
        );
    }

    public function inboxUrl() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('user.inbox', [$this]),
        );
    }

    public function outboxUrl() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('user.outbox', [$this]),
        );
    }

    public function followingUrl() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('user.following', [$this]),
        );
    }

    public function followersUrl() : Attribute
    {
        return Attribute::make(
            get: fn () : string => route('user.followers', [$this]),
        );
    }

    public function getStatuses() : Paginator
    {
        return $this->model::getStatuses();
    }

    public function getActorArray() : array
    {
        $person = [
            // 'type' => 'Person',
            'type' => 'Service', // Bot
            'id' => $this->activityId,
            'preferredUsername' => $this->username,
            'url' => $this->profile_url,
            'name' => $this->name,
            'summary' => $this->bio,
            // Avatar
            'icon' => [
                'type' => 'Image',
                'mediaType' => 'image/jpeg',
                'url' => asset($this->avatar),
            ],
            // Header
            'image' => [
                'type' => 'Image',
                'mediaType' => 'image/jpeg',
                'url' => asset($this->header),
            ],
        ];

        $metadata = [
            'tag' => [],
            'attachment' => Arr::wrap($this->properties),
            'discoverable' => true,
            // When the bot joined the fediverse (or it was created)
            'published' => $this->created_at->toAtomString(),
            // Crypto to sign messages
            'publicKey' => [
                'id' => $this->keyId,
                'owner' => $this->activityId,
                'publicKeyPem' => $this->publicKey,
            ],
        ];

        $links = [
            'inbox' => $this->inboxUrl,
            'outbox' => $this->outboxUrl,
            'following' => $this->followingUrl,
            'followers' => $this->followersUrl,
            'manuallyApprovesFollowers' => false,
            'endpoints' => [
                'sharedInbox' => route('shared-inbox'),
            ],
        ];

        return array_merge($person, $metadata, $links);
    }
}
