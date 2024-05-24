<?php

declare(strict_types=1);

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\Extended\Object\Image;

class Document extends Image
{
    protected string $blurhash;

    /** @var array<int, float> $focalPoint */
    protected array $focalPoint;

    protected int $width;

    protected int $height;
}
