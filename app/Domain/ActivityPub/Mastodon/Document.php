<?php

declare(strict_types=1);

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\Extended\Object\Image;

class Document extends Image
{
    /** @var string */
    protected $blurhash;

    /** @var array<int, float> $focalPoint */
    protected $focalPoint;

    /** @var int */
    protected $width;

    /** @var int */
    protected $height;
}
