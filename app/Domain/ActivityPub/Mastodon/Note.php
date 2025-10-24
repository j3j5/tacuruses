<?php

declare(strict_types=1);

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\Extended\Object\Note as ObjectNote;

class Note extends ObjectNote
{
    /** @var bool */
    protected $sensitive;
    /** @var string */
    protected $atomUri;
    /** @var string */
    protected $inReplyToAtomUri;
    /** @var string */
    protected $conversation;
    /** @var array */
    protected $interactionPolicy;

}
