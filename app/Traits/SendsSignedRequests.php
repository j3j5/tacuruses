<?php

namespace App\Traits;

use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

trait SendsSignedRequests
{
    /**
     *
     * @param \App\Models\ActivityPub\LocalActor $actorSigning
     * @param string $url
     * @param array $data
     * @throws \RuntimeException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \phpseclib3\Exception\NoKeyLoadedException
     * @throws \Exception
     * @throws \Illuminate\Http\Client\RequestException
     * @return \Illuminate\Http\Client\Response
     */
    private function sendSignedPostRequest(
        LocalActor $actorSigning,
        string $url,
        array $data,
    ) : Response {
        if (empty($url)) {
            throw new RuntimeException('URL cannot be empty');
        }

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/ld+json; profile="' . Context::ACTIVITY_STREAMS . '"',
            'User-Agent' => config('federation.user-agent'),
        ];

        /** @var \App\Services\ActivityPub\Signer $signer */
        $signer = app(Signer::class);
        $signer->setDigestAlgo('sha256')
            ->setKeyId($actorSigning->key_id)
            ->setPrivateKey($actorSigning->private_key);

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withHeaders($headers)->withMiddleware(
            Middleware::mapRequest([$signer, 'signRequest'])
        )->post($url, $data);

        if ($response->failed()) {
            Log::warning('Request failed', ['response' => $response]);
            $response->throw();
        }

        return $response;
    }
}
