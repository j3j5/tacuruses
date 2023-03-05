<?php

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\Core\ObjectType;

class Hashtag extends ObjectType
{
    protected $type = 'Hashtag';
    /** @var string */
    protected $href;
}
