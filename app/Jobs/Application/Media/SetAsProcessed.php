<?php

declare(strict_types=1);

namespace App\Jobs\Application\Media;

use App\Domain\Application\Media;
use Closure;

final class SetAsProcessed
{
    public function handle(Media $media, Closure $next) : mixed
    {
        $model = $media->getModel();
        $model->processed = true;
        $model->save();
        $media->setModel($model);

        return $next($media);
    }
}
