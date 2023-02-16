<?php

namespace App\Models\ActivityPub;

use App\Domain\ActivityPub\Contracts\Actor as ContractsActor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
}
