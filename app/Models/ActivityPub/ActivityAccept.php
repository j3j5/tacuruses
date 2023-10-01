<?php

namespace App\Models\ActivityPub;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;
use RuntimeException;

/**
 * App\Models\ActivityPub\ActivityAccept
 * @mixin \Eloquent
 */
class ActivityAccept extends Activity
{
    use HasFactory;
    use HasParent;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'object' => 'array',
    ];

    public function target() : BelongsTo
    {
        return match ($this->object_type) {
            'Follow' => $this->belongsTo(Actor::class, 'target_id'),
            'Like', 'Undo', 'Announce' => $this->belongsTo(Note::class, 'target_id'),
            default => throw new RuntimeException('Unknown Accept type "' . $this->object_type . '"'),
        };
    }
}
