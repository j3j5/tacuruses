<?php

namespace App\Domain\ActivityPub\Contracts;

use ActivityPhp\Type\Extended\Object\Note as ObjectNote;
use Carbon\Carbon;

interface Note
{
    public function getAPNote() : ObjectNote;

    public function getText() : string;

    public function getLanguage() : string;

    public function getPublishedStatusAt() : Carbon;

    public function getNoteUrl() : string;

    public function getActivityUrl() : string;

    public function getActor() : Actor;

    public function getAttachment() : array;

    public function getTags() : array;

    public function isSensitive() : bool;
}
