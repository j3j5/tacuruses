<?php

declare(strict_types=1);

namespace App\Jobs\Application\Media;

use App\Domain\Application\Media;
use App\Models\Media as ModelsMedia;
use Closure;

final class StoreOriginalMedia
{
    public function handle(Media $media, Closure $next) : mixed
    {
        $media->file->storePubliclyAs(
            path: '/',
            name: $media->getFilename(),
            options: [
                'disk' => config('filesystems.cloud'),
            ],
        );

        $mediaDb = ModelsMedia::create($media->getDataForModel());

        $media->setModel($mediaDb);

        return $next($media);
    }
}
