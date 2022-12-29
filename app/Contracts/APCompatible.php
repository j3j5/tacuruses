<?php

namespace App\Contracts;

use ActivityPhp\Type\Extended\Activity\Create;
use ActivityPhp\Type\Extended\Object\Note;
use App\Models\ActivityPub\LocalActor;
use Carbon\Carbon;

interface APCompatible
{
    public static function getActor() : LocalActor;

    public function getCreateActivity(): Create;

    public function getNote() : Note;

    public function getStatus() : string;

    public function getPublishedStatusAt() : Carbon;

    public function getStatusUrl() : string;

    public function getActivityUrl() : string;
}
