<?php

namespace App\Traits;

use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

trait SendsSignedRequests
{
    private function sendSignedRequest(
        Signer $signer,
        LocalActor $actorSigning,
        array $request,
        string $inbox
    ) : Response {
        if (!is_string(($inbox)) || empty($inbox)) {
            throw new RuntimeException('Actor\'s inbox url is empty');
        }

        $body = json_encode($request, JSON_THROW_ON_ERROR);
        // Make HTTP post back to the server of the actor

        $headers = $signer->sign(
            $actorSigning,
            $inbox,
            $body,
            [
                'Content-Type' => 'application/ld+json; profile="' . Context::ACTIVITY_STREAMS . '"',
                'User-Agent' => config('federation.user-agent'),
            ]
        );

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withHeaders($headers)->post($inbox, $request);
        if ($response->failed()) {
            Log::warning('Request failed. Response:', [$response]);
            $response->throw();
        }

        return $response;
    }
}
