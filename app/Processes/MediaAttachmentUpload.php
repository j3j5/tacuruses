<?php

declare(strict_types=1);

namespace App\Processes;

use App\Jobs\Application\Media\StoreOriginalMedia;

final class MediaAttachmentUpload extends Process
{
    protected array $tasks = [
        StoreOriginalMedia::class,
        // StoreThumbnail::class,
        // ProcessFileSizes::class,
        // CalculateHash::class,
    ];
}
