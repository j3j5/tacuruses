<?php

declare(strict_types=1);

namespace App\Domain\ActivityPub\Mastodon;

class Person extends AbstractActor
{
    /** @var string */
    protected $type = 'Person';
}
