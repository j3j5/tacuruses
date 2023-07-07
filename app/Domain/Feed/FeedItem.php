<?php

declare(strict_types=1);

namespace App\Domain\Feed;

use Illuminate\Support\Collection;
use Spatie\Feed\FeedItem as SpatieFeedItem;

class FeedItem extends SpatieFeedItem
{
    protected Collection $media;

    public function media(Collection $media) : self
    {
        $this->media = $media;

        return $this;
    }
}
