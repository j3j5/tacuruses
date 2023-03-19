<?php

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\Extended\Activity\Create as ActivityCreate;

class Create extends ActivityCreate
{
    /** @var array|\App\Domain\ActivityPub\Mastodon\RsaSignature2017 */
    protected $signature;
}
