<?php

declare(strict_types=1);

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\Core\ObjectType;

class RsaSignature2017 extends ObjectType
{
    protected $type = 'RsaSignature2017';

    /** @var string */
    protected $creator;

    /** @var string */
    protected $created;

    /** @var string */
    protected $signatureValue;
}
