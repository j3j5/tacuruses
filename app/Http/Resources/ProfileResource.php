<?php

namespace App\Http\Resources;

use App\Services\ActivityPub\Context;
use App\Traits\Resources\ActivityPubResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ActivityPub\LocalActor
 */
class ProfileResource extends JsonResource
{
    use ActivityPubResource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $context = [
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
                'Device' => 'toot:Device',
                'Ed25519Signature' => 'toot:Ed25519Signature',
                'Ed25519Key' => 'toot:Ed25519Key',
                'Curve25519Key' => 'toot:Curve25519Key',
                'EncryptedMessage' => 'toot:EncryptedMessage',
                'publicKeyBase64' => 'toot:publicKeyBase64',
                'deviceId' => 'toot:deviceId',
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
                'devices' => [
                    '@type' => '@id',
                    '@id' => 'toot:devices',
                ],
                'messageFranking' => 'toot:messageFranking',
                'messageType' => 'toot:messageType',
                'cipherText' => 'toot:cipherText',
                'suspended' => 'toot:suspended',
                'focalPoint' => [
                    '@container' => '@list',
                    '@id' => 'toot:focalPoint',
                ],
            ],
        ];
        $context = ['@context' => $context];

        $person = $this->getActorArray();

        return array_merge($context, $person);
    }
}
