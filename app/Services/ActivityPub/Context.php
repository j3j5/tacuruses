<?php

declare(strict_types = 1);

namespace App\Services\ActivityPub;

class Context
{
    public const ACTIVITY_STREAMS = 'https://www.w3.org/ns/activitystreams';
    public const ACTIVITY_STREAMS_PUBLIC = 'https://www.w3.org/ns/activitystreams#Public';
    public const W3ID_SECURITY = 'https://w3id.org/security/v1';

    public static array $actor = [
        Context::ACTIVITY_STREAMS,
        Context::W3ID_SECURITY,
        [
            'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
            'toot' => 'http://joinmastodon.org/ns#',
            'featured' => [
                '@id' => 'toot:featured',
                '@type' => '@id',
            ],
            'featuredTags' => [
                '@id' => 'toot:featuredTags',
                '@type' => '@id',
            ],
            'alsoKnownAs' => [
                '@id' => 'as:alsoKnownAs',
                '@type' => '@id',
            ],
            'movedTo' => [
                '@id' => 'as:movedTo',
                '@type' => '@id',
            ],
            'schema' => 'http://schema.org#',
            'PropertyValue' => 'schema:PropertyValue',
            'value' => 'schema:value',
            'discoverable' => 'toot:discoverable',
            // 'Device' => 'toot:Device',
            'Ed25519Signature' => 'toot:Ed25519Signature',
            'Ed25519Key' => 'toot:Ed25519Key',
            'Curve25519Key' => 'toot:Curve25519Key',
            'EncryptedMessage' => 'toot:EncryptedMessage',
            'publicKeyBase64' => 'toot:publicKeyBase64',
            // 'deviceId' => 'toot:deviceId',
            'claim' => [
                '@type' => '@id',
                '@id' => 'toot:claim',
            ],
            'fingerprintKey' => [
                '@type' => '@id',
                '@id' => 'toot:fingerprintKey',
            ],
            'identityKey' => [
                '@type' => '@id',
                '@id' => 'toot:identityKey',
            ],
            // 'devices' => [
            //     '@type' => '@id',
            //     '@id' => 'toot:devices',
            // ],
            // 'messageFranking' => 'toot:messageFranking',
            'messageType' => 'toot:messageType',
            'cipherText' => 'toot:cipherText',
            'suspended' => 'toot:suspended',
            'focalPoint' => [
                '@container' => '@list',
                '@id' => 'toot:focalPoint',
            ],
        ],
    ];

    public static array $status = [
        // 'ostatus' => 'http://ostatus.org#',
        // 'atomUri' => 'ostatus:atomUri',
        // 'inReplyToAtomUri' => 'ostatus:inReplyToAtomUri',
        // 'conversation' => 'ostatus:conversation',
        'sensitive' => 'as:sensitive',
        'toot' => 'http://joinmastodon.org/ns#',
        // 'votersCount' => 'toot:votersCount',
        'Hashtag' => 'as:Hashtag',
        'Emoji' => 'toot:Emoji',
        'blurhash' => 'toot:blurhash',
        'focalPoint' => [
            '@container' => '@list',
            '@id' => 'toot:focalPoint',
        ],
    ];
}
