<?php

namespace App\Models\ActivityPub;

use App\Events\RemoteActorCreated;
use App\Events\RemoteActorUpdated;
use App\Jobs\ActivityPub\DeliverActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Parental\HasParent;

use function Safe\parse_url;

/**
 * App\Models\ActivityPub\RemoteActor
 *
 * @phpstan-type InstanceUser array{id: string, type: string, preferredUsername: string, name: string, summary: ?string, url: string, icon: array<string,string>, image: array<string,string>, inbox: string, outbox: string, following: string, followers: string, endpoints: array<string,string>, publicKey: array<string,string> }
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property string $username
 * @property string|null $avatar
 * @property string|null $header
 * @property string|null $bio
 * @property string|null $alsoKnownAs
 * @property string|null $properties
 * @property string $language
 * @property string|null $activityId
 * @property string|null $type
 * @property string|null $url
 * @property string $inbox
 * @property string|null $sharedInbox
 * @property string|null $publicKeyId
 * @property string|null $publicKey
 * @property string|null $actor_type
 * @property string|null $followers_url
 * @property string|null $following_url
 * @property string|null $outbox
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $allNotes
 * @property-read int|null $all_notes_count
 * @property-read string $domain
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $drafts
 * @property-read int|null $drafts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Follow> $following
 * @property-read int|null $following_count
 * @property-read string $full_username
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Like> $liked
 * @property-read int|null $liked_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Note> $notes
 * @property-read int|null $notes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shared
 * @property-read int|null $shared_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityPub\Share> $shares
 * @property-read int|null $shares_count
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor query()
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereActorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereAlsoKnownAs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereFollowersUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereFollowingUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereHeader($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereInbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereOutbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor wherePublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor wherePublicKeyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereSharedInbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereUsername($value)
 * @mixin \Eloquent
 */
class RemoteActor extends Actor
{
    use HasFactory;
    use HasParent;

    protected $fillable = ['activityId', 'type', 'actor_type'];

    /**
     * Create an Actor model from the data returned from an instance
     * @param array $data
     * @phpstan-param InstanceUser $data
     *
     */
    public function updateFromInstanceData(array $data) : self
    {
        $this->activityId = $data['id'];
        $this->type = $data['type'];
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

    public function domain() : Attribute
    {
        return Attribute::make(
            get: fn () : string => parse_url($this->url, PHP_URL_HOST), /* @phpstan-ignore-line */
        );
    }

    public function fullUsername() : Attribute
    {
        return Attribute::make(
            get: fn () : string => '@' . $this->username . '@' . $this->domain,
        );
    }

    public function sendNote(LocalNote $note) : self
    {
        $inbox = empty($this->sharedInbox) ? $this->inbox : $this->sharedInbox;
        if (!is_string($inbox) || empty($inbox)) {
            Log::warning('Actor does not seem to have a valid inbox');
            return $this;
        }

        Log::debug('dispatching job to deliver the Create activity for a note', ['actor' => $this, 'note' => $note]);

        DeliverActivity::dispatch($note->actor, $note->getAPActivity(), $inbox);

        return $this;
    }
}
