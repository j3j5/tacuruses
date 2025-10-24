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
            // 'Ed25519Signature' => 'toot:Ed25519Signature',
            // 'Ed25519Key' => 'toot:Ed25519Key',
            // 'Curve25519Key' => 'toot:Curve25519Key',
            // 'EncryptedMessage' => 'toot:EncryptedMessage',
            // 'publicKeyBase64' => 'toot:publicKeyBase64',
            // 'deviceId' => 'toot:deviceId',
            // 'claim' => [
            //     '@type' => '@id',
            //     '@id' => 'toot:claim',
            // ],
            // 'fingerprintKey' => [
            //     '@type' => '@id',
            //     '@id' => 'toot:fingerprintKey',
            // ],
            // 'identityKey' => [
            //     '@type' => '@id',
            //     '@id' => 'toot:identityKey',
            // ],
            // 'devices' => [
            //     '@type' => '@id',
            //     '@id' => 'toot:devices',
            // ],
            // 'messageFranking' => 'toot:messageFranking',
            // 'messageType' => 'toot:messageType',
            // 'cipherText' => 'toot:cipherText',
            'suspended' => 'toot:suspended',
            'memorial' => 'toot:memorial',
            'indexable' => 'toot:indexable',
            'attributionDomains' => [
                '@id' => 'toot:attributionDomains',
                '@type' => '@id',
            ],
            'Hashtag' => 'as:Hashtag',
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
        'votersCount' => 'toot:votersCount',
        'quote' => 'https://w3id.org/fep/044f#quote',
        'quoteUri' => 'http://fedibird.com/ns#quoteUri',
        '_misskey_quote' => 'https://misskey-hub.net/ns#_misskey_quote',
        'quoteAuthorization' => [
            '@id' => 'https://w3id.org/fep/044f#quoteAuthorization',
            '@type' => '@id',
        ],
        'gts' => 'https://gotosocial.org/ns#',
        'interactionPolicy' => [
            '@id' => 'gts:interactionPolicy',
            '@type' => '@id',
        ],
        'canQuote' => ['@id' => 'gts:canQuote', '@type' => '@id'],
        'automaticApproval' => [
            '@id' => 'gts:automaticApproval',
            '@type' => '@id',
        ],
        'manualApproval' => ['@id' => 'gts:manualApproval', '@type' => '@id'],
        'Hashtag' => 'as:Hashtag',
        'Emoji' => 'toot:Emoji',
        'blurhash' => 'toot:blurhash',
        'focalPoint' => [
            '@container' => '@list',
            '@id' => 'toot:focalPoint',
        ],
    ];

    public static array $quoteAuth = [
        'QuoteAuthorization' => 'https://w3id.org/fep/044f#QuoteAuthorization',
        'gts' => 'https://gotosocial.org/ns#',
        'interactingObject' => [
            '@id' => 'gts:interactingObject',
            '@type' => '@id',
        ],
        'interactionTarget' => [
            '@id' => 'gts:interactionTarget',
            '@type' => '@id',
        ],
    ];

    public static array $quoteRequest = [
        'QuoteRequest' => 'https://w3id.org/fep/044f#QuoteRequest',
    ];
}
