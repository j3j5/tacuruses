<?php

namespace App\Models;

use App\Traits\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

use function Safe\getimagesize;

class Media extends Model
{
    use HasFactory;
    use HasSnowflakePrimary;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'processed' => 'boolean',
        'file_updated_at' => 'datetime',
        'meta' => 'array',
    ];

    public function width() : Attribute
    {
        return Attribute::make(
            get: fn () => 1200
            // get: fn () => Arr::get($this->meta, 'original.width', Arr::get(getimagesize($this->remote_url), 0))
        );
    }

    public function height() : Attribute
    {
        return Attribute::make(
            get: fn () => 780
            // get: fn () => Arr::get($this->meta, 'original.height', Arr::get(getimagesize($this->remote_url), 1))
        );
    }
}
