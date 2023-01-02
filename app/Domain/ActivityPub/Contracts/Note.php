<?php

namespace App\Domain\ActivityPub\Contracts;

use ActivityPhp\Type\Extended\Object\Note as ObjectNote;
use Carbon\Carbon;

interface Note
{

    public function getAPNote() : ObjectNote;

    public function getText() : string;

    public function getPublishedStatusAt() : Carbon;

    public function getStatusUrl() : string;

    public function getActivityUrl() : string;
}
