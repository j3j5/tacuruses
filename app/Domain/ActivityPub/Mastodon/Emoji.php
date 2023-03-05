<?php

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\Core\ObjectType;

class Emoji extends ObjectType
{
    protected $type = 'Emoji';
    /** @var string */
    protected $updated;
    /** @var \ActivityPhp\Type\Extended\Object\Image */
    protected $icon;
}
