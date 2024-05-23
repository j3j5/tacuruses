<?php

declare(strict_types=1);

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\Core\ObjectType;

class PropertyValue extends ObjectType
{
    protected $type = 'PropertyValue';

    /** @var string */
    protected $value;
}
