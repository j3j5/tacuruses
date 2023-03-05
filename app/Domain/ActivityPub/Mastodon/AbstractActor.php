<?php

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\Extended\AbstractActor as ExtendedAbstractActor;

class AbstractActor extends ExtendedAbstractActor
{
    /** @var string */
    protected $featured;
    /** @var string */
    protected $featuredTags;
    /** @var bool */
    protected $manuallyApprovesFollowers;
    /** @var bool */
    protected $discoverable;
    /** @var string */
    protected $devices;
    /** @var array<int,string> */
    protected $alsoKnownAs;
}
