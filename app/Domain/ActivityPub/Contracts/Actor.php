<?php

namespace App\Domain\ActivityPub\Contracts;

use Illuminate\Contracts\Pagination\Paginator;

/**
 * @property-read string $key_id
 * @property-read string $private_key
 *
 * @package App\Domain\ActivityPub\Contracts
 */
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

    public function getKeyId() : string;
    public function getPrivateKey() : string;
}
