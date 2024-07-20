<?php

declare(strict_types = 1);

namespace App\Traits;

use App\Exceptions\FederationConnectionException;
use App\Exceptions\FederationDeliveryException;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webmozart\Assert\Assert;

trait SendsSignedRequests
{

    /**
     *
     * @param \App\Services\ActivityPub\Signer $signer
     * @param string $url
     * @param \App\Models\ActivityPub\LocalActor $actorSigning
     * @param array<string, string> $headers
     * @param array<callable> $middlewares
     * @throws \InvalidArgumentException
     * @throws \App\Exceptions\FederationConnectionException
     * @throws \App\Exceptions\FederationDeliveryException
     * @return \Illuminate\Http\Client\Response
     */
    private function sendSignedGetRequest(
        Signer $signer,
        string $url,
        ?LocalActor $actorSigning = null,
        array $headers = [],
        array $middlewares = []
    ) : Response {
        Assert::notEmpty($url);

        // If no actor provided, use an "admin" (or the oldest user)
        if ($actorSigning === null) {
            $actorSigning = LocalActor::oldest()->first();
        }

        $headers = array_merge($headers, [
            'Accept' => 'application/activity+json',
        ]);

        $signer->setDigestAlgo('sha256')
            ->setKeyId($actorSigning->publicKeyId)
            ->setPrivateKey($actorSigning->privateKey);

        $middlewares = array_values(Arr::prepend($middlewares, Middleware::mapRequest([$signer, 'signRequest'])));
        $request = Http::withHeaders($headers);
        foreach($middlewares as $middleware) {
            $request->withMiddleware($middleware);
        }
        Log::debug('doing signed GET request', ['url' => $url]);
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $request->get($url);
        } catch (ConnectionException|RequestException $e) {
            throw new FederationConnectionException($url, $e);
        }

        if ($response->failed()) {
            throw new FederationDeliveryException($response);
        }

        return $response;
    }

    /**
     *
     * @param \App\Services\ActivityPub\Signer $signer
     * @param \App\Models\ActivityPub\LocalActor $actorSigning
     * @param string $url
     * @param array $data
     * @param array<callable> $middlewares
     * @throws \InvalidArgumentException
     * @throws \App\Exceptions\FederationConnectionException
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

        Assert::notEmpty($url);

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
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $request->post($url, $data);
        } catch (ConnectionException|RequestException $e) {
            throw new FederationConnectionException($url, $e);
        }

        if ($response->failed()) {
            throw new FederationDeliveryException($response);
        }

        return $response;
    }
}
