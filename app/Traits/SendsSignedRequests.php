<?php

declare(strict_types = 1);

namespace App\Traits;

use App\Exceptions\FederationConnectionException;
use App\Exceptions\FederationDeliveryException;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

trait SendsSignedRequests
{
    /**
     *
     * @param \App\Services\ActivityPub\Signer $signer
     * @param \App\Models\ActivityPub\LocalActor $actorSigning
     * @param string $url
     * @param array $data
     * @param array<callable> $middlewares
     * @throws \RuntimeException
     * @throws \App\Exceptions\FederationDeliveryException
     * @return \Illuminate\Http\Client\Response
     */
    private function sendSignedPostRequest(
        Signer $signer,
        LocalActor $actorSigning,
        string $url,
        array $data,
        array $middlewares = []
    ) : Response {
        if (empty($url)) {
            throw new RuntimeException('URL cannot be empty');
        }

        $headers = [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/ld+json; profile="' . Context::ACTIVITY_STREAMS . '"',
        ];

        $signer->setDigestAlgo('sha256')
            ->setKeyId($actorSigning->publicKeyId)
            ->setPrivateKey($actorSigning->privateKey);

        $middlewares = array_values(Arr::prepend($middlewares, Middleware::mapRequest([$signer, 'signRequest'])));
        $request = Http::withHeaders($headers);
        foreach($middlewares as $middleware) {
            $request->withMiddleware($middleware);
        }
        Log::debug('sending signed request', ['url' => $url, 'data' => $data]);
        /** @var \Illuminate\Http\Client\Response $response */
        try {
            $response = $request->post($url, $data);
        } catch (ConnectionException $e) {
            throw new FederationConnectionException($url);
        }

        if ($response->failed()) {
            Log::warning('Request failed', ['response' => $response]);
            throw new FederationDeliveryException($response);
        }

        return $response;
    }
}
