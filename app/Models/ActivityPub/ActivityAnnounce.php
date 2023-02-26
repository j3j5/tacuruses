<?php

namespace App\Models\ActivityPub;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;

/**
 * App\Models\ActivityPub\ActivityAnnounce
 *
 * @property-read \App\Models\ActivityPub\Note|null $target
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAnnounce newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAnnounce newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityAnnounce query()
 * @mixin \Eloquent
 */
class ActivityAnnounce extends Activity
{
    use HasFactory;
    use HasParent;

    public function target() : BelongsTo
    {
        return $this->belongsTo(Note::class, 'target_id');
    }
}
