<?php

namespace App\Models\ActivityPub;

use App\Domain\ActivityPub\Contracts\Actor as ContractsActor;
use App\Domain\ActivityPub\Contracts\Note;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Parental\HasParent;

/**
 * App\Models\ActivityPub\LocalActor
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $model
 * @property string $name
 * @property string $username
 * @property string $avatar
 * @property string $header
 * @property string|null $bio
 * @property array|null $alsoKnownAs
 * @property array|null $properties
 * @property-read string $activity_id
 * @property-read string $inbox_url
 * @property-read string $key_id
 * @property-read string $outbox_url
 * @property-read string $private_key
 * @property-read string $public_key
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor query()
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereAlsoKnownAs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereHeader($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LocalActor whereUsername($value)
 * @mixin \Eloquent
 */
class LocalActor extends Actor implements ContractsActor
{
    use HasFactory;
    use HasParent;

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

    public function avatar() : Attribute
    {
        return Attribute::make(
            get: fn ($value) : string => $value ?: 'img/default_avatar.svg',
        );
    }

    public function header() : Attribute
    {
        return Attribute::make(
            get: fn ($value) : string => $value ?: 'img/default_avatar.svg',
        );
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
            get: fn () : string => (string) Storage::disk('local')->get("keys/local/{$this->id}/public.pem"),
        );
    }

    public function privateKey() : Attribute
    {
        return Attribute::make(
            get: fn () : string => (string) Storage::disk('local')->get("keys/local/{$this->id}/private.pem"),
        );
    }

    public function getProfileUrl() : string
    {
        return route('user.show', [$this]);
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

    public function getFollowingUrl() : string
    {
        return  route('user.following', [$this]);
    }

    public function getFollowersUrl() : string
    {
        return route('user.followers', [$this]);
    }

    public function getAvatarURL(): string
    {
        return asset($this->avatar);
    }

    public function getHeaderURL(): string
    {
        return asset($this->header);
    }

    /**
     *
     * @param string $noteId
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @return \App\Domain\ActivityPub\Contracts\Note
     */
    public function getNote(string $noteId): Note
    {
        return $this->model::findOrFail($noteId);
    }

    public function getNotes() : Paginator
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
                'url' => $this->getAvatarURL(),
            ],
            // Header
            'image' => [
                'type' => 'Image',
                'mediaType' => 'image/jpeg',
                'url' => $this->getHeaderURL(),
            ],
        ];

        $metadata = [
            'tag' => [],
            'attachment' => Arr::wrap($this->properties),
            'discoverable' => true,
            // When the bot joined the fediverse (or it was created)
            // Crypto to sign messages
            'publicKey' => [
                'id' => $this->keyId,
                'owner' => $this->activityId,
                'publicKeyPem' => $this->publicKey,
            ],
        ];
        if ($this->created_at instanceof Carbon) {
            $metadata['published'] = $this->created_at->toAtomString();
        }

        $links = [
            'inbox' => $this->inboxUrl,
            'outbox' => $this->outboxUrl,
            'following' => $this->getFollowingUrl(),
            'followers' => $this->getFollowersUrl(),
            'manuallyApprovesFollowers' => false,
            'endpoints' => [
                'sharedInbox' => route('shared-inbox'),
            ],
        ];

        return array_merge($person, $metadata, $links);
    }
}
