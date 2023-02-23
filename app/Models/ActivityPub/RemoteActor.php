<?php

namespace App\Models\ActivityPub;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Parental\HasParent;

/**
 * App\Models\ActivityPub\RemoteActor
 *
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor query()
 * @mixin \Eloquent
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $activityId
 * @property string $type
 * @property string $username
 * @property string $name
 * @property string|null $bio
 * @property string $url
 * @property string|null $avatar
 * @property string|null $header
 * @property string $inbox
 * @property string $sharedInbox
 * @property string $publicKeyId
 * @property string $publicKey
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ActivityPub\Follow[] $follows
 * @property-read int|null $follows_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ActivityPub\Like[] $likes
 * @property-read int|null $likes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ActivityPub\Share[] $shares
 * @property-read int|null $shares_count
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereHeader($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereInbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor wherePublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor wherePublicKeyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereSharedInbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereUsername($value)
 * @phpstan-type InstanceUser array{id: string, type: string, preferredUsername: string, name: string, summary: ?string, url: string, icon:array<string,string>, image: array<string,string>, inbox: string, endpoints: array<string, string>, publicKey: array<string, string> }
 * @property string|null $model
 * @property string|null $alsoKnownAs
 * @property string|null $properties
 * @property string|null $actor_type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ActivityPub\Follow[] $followers
 * @property-read int|null $followers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ActivityPub\Follow[] $following
 * @property-read int|null $following_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ActivityPub\Like[] $liked
 * @property-read int|null $liked_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ActivityPub\Share[] $shared
 * @property-read int|null $shared_count
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereActorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereAlsoKnownAs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RemoteActor whereProperties($value)
 */
class RemoteActor extends Actor
{
    use HasFactory;
    use HasParent;

    protected $fillable = ['activityId', 'type'];

    /**
     * Create an Actor model from the data returned from an instance
     * @param InstanceUser $data
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
        $this->sharedInbox = Arr::get($data, 'endpoints.sharedInbox');
        $this->publicKeyId = Arr::get($data, 'publicKey.id');
        $this->publicKey = Arr::get($data, 'publicKey.publicKeyPem');
        $this->save();
        return $this;
    }
}
