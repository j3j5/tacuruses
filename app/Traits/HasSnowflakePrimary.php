<?php

namespace App\Traits;

use App\Contracts\Snowflake;

trait HasSnowflakePrimary
{
    public static function bootHasSnowflakePrimary() : void
    {
        static::saving(function ($model) {
            if (is_null($model->getKey())) {
                $model->setIncrementing(false);
                $keyName = $model->getKeyName();
                $id = app()->make(Snowflake::class)->id();
                $model->setAttribute($keyName, $id);
            }
        });
    }
}
