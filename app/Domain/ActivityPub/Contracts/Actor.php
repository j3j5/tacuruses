<?php

namespace App\Domain\ActivityPub\Contracts;

use Illuminate\Support\Collection;

interface Actor
{

    /**
     *
     * @return \Illuminate\Support\Collection<\App\Domain\ActivityPub\Contracts\Note>
     */
    public function getNotes() : Collection;

}
