<?php

declare(strict_types=1);

namespace App\Traits;

use App\Contracts\Snowflake;
use Illuminate\Database\Eloquent\Model;

/**
 * This trait was originally copied from the kra8/laravel-snowflake
 * project which is MIT licensed. I've since modified it to fit
 * the project needs.
 *
 * @url https://github.com/kra8/laravel-snowflake/blob/5fe9ab806c630b91b2c61ed1528fbc7926f7467f/LICENSE
 * @url https://github.com/kra8/laravel-snowflake/blob/5fe9ab806c630b91b2c61ed1528fbc7926f7467f/src/HasSnowflakePrimary.php
 */
trait HasSnowflakePrimary
{
    public static function bootHasSnowflakePrimary() : void
    {
        static::saving(function (Model $model) {
            if (
                // Empty
                $model->getKey() === null
                // added to deal with Laravel forcing UUIDs on me for Notifications
                || !is_int($model->getKey())
            ) {
                $model->setIncrementing(false);
                $model->setAttribute(
                    $model->getKeyName(),
                    app()->make(Snowflake::class)->id()
                );
            }
        });
    }
}
