<?php

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\Extended\AbstractActor as ExtendedAbstractActor;

class AbstractActor extends ExtendedAbstractActor
{
    protected $featured;
    protected $featuredTags;
    protected $manuallyApprovesFollowers;
    protected $discoverable;
    protected $devices;
    protected $alsoKnownAs;
}
