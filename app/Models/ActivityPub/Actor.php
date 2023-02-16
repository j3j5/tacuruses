<?php

namespace App\Models\ActivityPub;

use App\Domain\ActivityPub\Contracts\Actor as ContractsActor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Parental\HasChildren;

class Actor extends Model
{
    use HasFactory;
    use HasChildren;

    // protected $guarded = ['id'];
    protected $fillable = ['type'];

    protected $childTypes = [
        'local' => LocalActor::class,
        'remote' => RemoteActor::class,
    ];

    protected $childColumn = 'actor_type';

    public function following() : HasMany
    {
        return $this->hasMany(Follow::class);
    }

    public function followers() : HasMany
    {
        return $this->hasMany(Follow::class, 'target_id');
    }

    public function liked() : HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function likes() : HasMany
    {
        return $this->hasMany(Like::class, 'target_id');
    }

    public function shared() : HasMany
    {
        return $this->hasMany(Share::class);
    }

    public function shares() : HasMany
    {
        return $this->hasMany(Share::class, 'target_id');
    }
}
