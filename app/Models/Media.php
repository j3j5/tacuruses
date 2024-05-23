<?php

declare(strict_types = 1);

namespace App\Models;

use App\Traits\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

use function Safe\getimagesize;

/**
 * App\Models\Media
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $note_id
 * @property int $actor_id
 * @property string $description Alt text for the file
 * @property string|null $filename local filename
 * @property string|null $content_type mime type
 * @property string|null $filesize
 * @property \Illuminate\Support\Carbon|null $file_updated_at last datetime the file was updated
 * @property string|null $remote_url
 * @property array|null $meta
 * @property string $hash
 * @property bool $processed
 * @property string|null $thumb_filename local filename
 * @property string|null $thumb_content_type mime type
 * @property string|null $thumb_filesize
 * @property string|null $thumb_updated_at last datetime the file was updated
 * @property string|null $thumb_remote_url
 * @property-read string|int $height
 * @property-read string|int $width
 * @method static \Illuminate\Database\Eloquent\Builder|Media newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Media newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Media query()
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereContentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereFileUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereFilesize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereNoteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereProcessed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereRemoteUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereThumbContentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereThumbFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereThumbFilesize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereThumbRemoteUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereThumbUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Media extends Model
{
    use HasFactory;
    use HasSnowflakePrimary;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'processed' => 'boolean',
            'file_updated_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function width() : Attribute
    {
        return Attribute::make(
            get: fn () : string => '1200'
            // get: fn () => Arr::get($this->meta, 'original.width', Arr::get(getimagesize($this->remote_url), 0))
        );
    }

    public function height() : Attribute
    {
        return Attribute::make(
            get: fn () : string => '780'
            // get: fn () => Arr::get($this->meta, 'original.height', Arr::get(getimagesize($this->remote_url), 1))
        );
    }

    public function hash() : Attribute
    {
        return Attribute::make(
            get: fn (?string $value) : string => $value ?? 'UDRW0cIT~q-;t8WAM{V@_3V@D%kC4To0%LR*'
        );
    }

}
