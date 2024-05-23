<?php

declare(strict_types=1);

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\Core\ObjectType;

class Hashtag extends ObjectType
{
    protected $type = 'Hashtag';
    /** @var string */
    protected $href;
}
