<?php

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\Extended\Activity\Question as ActivityQuestion;

class Question extends ActivityQuestion
{
    /** @var string */
    protected $atomUri;
    /** @var string */
    protected $inReplyToAtomUri;
    /** @var string */
    protected $conversation;
    /** @var bool */
    protected $sensitive;
    /** @var int */
    protected $votersCount;
}
