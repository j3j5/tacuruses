<?php

declare(strict_types = 1);

namespace App\Models\ActivityPub;

use ActivityPhp\Type;
use App\Domain\ActivityPub\Mastodon\AbstractActor;
use App\Enums\ActorTypes;
use App\Events\RemoteActorCreated;
use App\Events\RemoteActorUpdated;
use App\Jobs\ActivityPub\DeliverActivity;
use App\Services\ActivityPub\Context;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Parental\HasParent;
use Stevebauman\Purify\Casts\PurifyHtmlOnGet;

/**
 * App\Models\ActivityPub\RemoteActor
 *
 * @phpstan-type InstanceUser array{id: string, type: string, preferredUsername: string, name: string, summary: ?string, url: string, icon: array<string,string>, image: array<string,string>, inbox: string, outbox: string, following: string, followers: string, endpoints: array<string,string>, publicKey: array<string,string> }
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $name
 * @property string $username
 * @property string|null $avatar
 * @property string|null $header
 * @property string|array|null $bio
 * @property array<array-key, mixed>|null $alsoKnownAs
 * @property array<array-key, mixed>|null $properties
 * @property string $language
 * @property string|null $activityId
 * @property ActorTypes|null $type
 * @property string|null $url
 * @property string|null $followers_url
 * @property string|null $following_url
 * @property string|null $inbox
 * @property string|null $outbox
 * @property string|null $sharedInbox
 * @property string|null $publicKeyId
 * @property string|null $publicKey
 * @property string|null $actor_type
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $allNotes
 * @property-read int|null $all_notes_count
 * @property-read string $canonical_username
 * @property-read string $domain
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $drafts
 * @property-read int|null $drafts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Actor> $followers
 * @property-read int|null $followers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Actor> $following
 * @property-read int|null $following_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Follow> $follows
 * @property-read int|null $follows_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $liked
 * @property-read int|null $liked_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $notes
 * @property-read int|null $notes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $notesWithReplies
 * @property-read int|null $notes_with_replies_count
 * @property-read \phpseclib3\Crypt\Common\PublicKey $public_key_object
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Follow> $receivedFollows
 * @property-read int|null $received_follows_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shared
 * @property-read int|null $shared_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shares
 * @property-read int|null $shares_count
 * @method static \Database\Factories\ActivityPub\RemoteActorFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereActorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereAlsoKnownAs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereFollowersUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereFollowingUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereHeader($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereInbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereOutbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor wherePublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor wherePublicKeyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereSharedInbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteActor whereUsername($value)
 * @mixin \Eloquent
 */
class RemoteActor extends Actor
{
    use HasFactory;
    use HasParent;

    protected $fillable = ['activityId', 'type', 'actor_type'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bio' => PurifyHtmlOnGet::class . ':mastodon',
            'alsoKnownAs' => 'array',
            'properties' => 'array',
            'type' => ActorTypes::class,
        ];
    }

    /**
     * Create an Actor model from the data returned from an instance
     * @param array $data
     * @phpstan-param InstanceUser $data
     *
     */
    public function updateFromInstanceData(array $data) : self
    {
        $this->activityId = $data['id'];
        $this->type = ActorTypes::from($data['type']);
        $this->username = $data['preferredUsername'];
        $this->name = $data['name'];
        $this->bio = Arr::get($data, 'summary');
        $this->url = $data['url'];
        $this->avatar = Arr::get($data, 'icon.url');
        $this->header = Arr::get($data, 'image.url');
        $this->inbox = $data['inbox'];
        $this->outbox = Arr::get($data, 'outbox');
        $this->following_url = Arr::get($data, 'following');
        $this->followers_url = Arr::get($data, 'followers');
        $this->sharedInbox = Arr::get($data, 'endpoints.sharedInbox');
        $this->publicKeyId = Arr::get($data, 'publicKey.id');
        $this->publicKey = Arr::get($data, 'publicKey.publicKeyPem');
        $this->alsoKnownAs = Arr::get($data, 'alsoKnownAs', '');
        $this->properties = Arr::only($data, ['tag', 'attachment', 'icon', 'image']);
        $this->save();

        if ($this->wasRecentlyCreated) {
            RemoteActorCreated::dispatch($this);
        } else {
            RemoteActorUpdated::dispatch($this);
        }

        return $this;
    }

    public function avatar() : Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? 'https://source.boringavatars.com/' : $value,
        );
    }

    public function sendNote(LocalNote $note) : self
    {
        $inbox = empty($this->sharedInbox) ? $this->inbox : $this->sharedInbox;
        if (empty($inbox)) {
            Log::warning('Actor does not seem to have a valid inbox');
            return $this;
        }

        Log::debug('dispatching job to deliver the Create activity for a note', [
            'actor' => $this->id,
            'note' => $note->id,
        ]);

        DeliverActivity::dispatch($note->actor, $note->getAPActivity(), $inbox);

        return $this;
    }

    public function getAPActor() : AbstractActor
    {
        $context = ['@context' => Context::$actor];

        $actor = $this->getActorArray();

        /** @phpstan-ignore-next-line */
        return Type::create($this->type->value, array_merge($context, $actor));
    }

    private function getActorArray() : array
    {
        $person = [
            'id' => $this->activityId,
            'preferredUsername' => $this->username,
            'url' => $this->url,
            'name' => $this->name,
            'summary' => $this->bio,
            // Avatar
            'icon' => Arr::get($this->properties, 'icon', [
                'type' => 'Image',
                'mediaType' => 'image/png',
                'url' => $this->avatar,
            ]),
            // Header
            'image' => Arr::get($this->properties, 'image', [
                'type' => 'Image',
                'mediaType' => 'image/png',
                'url' => $this->header,
            ]),
        ];

        $metadata = [
            'tag' => Arr::get($this->properties, 'tag', []),
            'attachment' => Arr::get($this->properties, 'attachment', []),
            'discoverable' => true,
            // Crypto to sign messages
            'publicKey' => [
                'id' => $this->publicKeyId,
                'owner' => $this->activityId,
                'publicKeyPem' => $this->publicKey,
            ],
        ];

        if ($this->created_at instanceof Carbon) {
            $metadata['published'] = $this->created_at->toAtomString();
        }

        $links = [
            'inbox' => $this->inbox,
            'outbox' => $this->outbox,
            'following' => $this->following_url,
            'followers' => $this->followers_url,
            'manuallyApprovesFollowers' => false,   // TODO: Move to the DB
            'endpoints' => [
                'sharedInbox' => $this->sharedInbox,
            ],
        ];

        return array_merge($person, $metadata, $links);
    }

}
