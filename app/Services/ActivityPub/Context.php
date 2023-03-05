<?php

namespace App\Services\ActivityPub;

class Context
{
    public const ACTIVITY_STREAMS = 'https://www.w3.org/ns/activitystreams';
    public const ACTIVITY_STREAMS_PUBLIC = 'https://www.w3.org/ns/activitystreams#Public';
    public const W3ID_SECURITY = 'https://w3id.org/security/v1';

    public static array $status = [
        'ostatus' => 'http://ostatus.org#',
        'atomUri' => 'ostatus:atomUri',
        'inReplyToAtomUri' => 'ostatus:inReplyToAtomUri',
        'conversation' => 'ostatus:conversation',
        'sensitive' => 'as:sensitive',
        'toot' => 'http://joinmastodon.org/ns#',
        'votersCount' => 'toot:votersCount',
    ];
}
