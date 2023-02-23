<?php

namespace App\Domain\ActivityPub\Contracts;

interface Signer
{
    /**
     * Given an actor, a URL
     * @param \App\Domain\ActivityPub\Contracts\Actor $user
     * @param string $url
     * @param null|string $body
     * @param array $extraHeaders
     * @return array
     */
    public function sign(Actor $user, string $url, ?string $body = null, array $extraHeaders = []) : array;
}
