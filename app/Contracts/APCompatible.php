<?php

namespace App\Contracts;

use ActivityPhp\Type\Extended\Activity\Create;
use ActivityPhp\Type\Extended\Object\Note;
use App\Models\ActivityPub\LocalActor;

interface APCompatible
{
    public static function getActor() : LocalActor;

    public function getCreateActivity(): Create;

    public function getNote() : Note;

    public function getStatus() : string;

    public function getStatusUrl() : string;

    public function getActivityUrl() : string;
}
