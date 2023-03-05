<?php

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\Extended\Activity\Create as ActivityCreate;

class Create extends ActivityCreate
{
    /** @var array */
    protected $signature;
}
