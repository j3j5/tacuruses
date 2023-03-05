<?php

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\Extended\Object\Note as ObjectNote;

class Note extends ObjectNote
{
    protected $sensitive;
    protected $atomUri;
    protected $inReplyToAtomUri;
    protected $conversation;
}
