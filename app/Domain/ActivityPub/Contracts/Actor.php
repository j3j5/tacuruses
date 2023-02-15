<?php

namespace App\Domain\ActivityPub\Contracts;

use Illuminate\Contracts\Pagination\Paginator;

interface Actor
{
    public function getProfileUrl() : string;
    public function getFollowersUrl() : string;
    public function getFollowingUrl() : string;
    public function getActorArray() : array;
    public function getAvatarURL() : string;
    public function getHeaderURL() : string;

    public function getNote(string $noteId) : Note;

    public function getNotes() : Paginator;
}
