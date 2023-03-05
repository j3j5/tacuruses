<?php

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\Core\CollectionPage as CoreCollectionPage;

class CollectionPage extends CoreCollectionPage
{
    protected $items;
}
