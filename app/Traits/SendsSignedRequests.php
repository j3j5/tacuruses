<?php

namespace App\Traits;

use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

trait SendsSignedRequests
{
    private function sendSignedRequest(Signer $signer, array $request) : Response
    {
        if (!is_string(($this->actor->inbox)) || empty($this->actor->inbox)) {
            throw new RuntimeException('Actor\'s inbox url is empty');
        }

        $body = json_encode($request, JSON_THROW_ON_ERROR);
        // Make HTTP post back to the server of the actor

        $headers = $signer->sign(
            $this->targetActor,
            $this->actor->inbox,
            $body,
            [
                'Content-Type' => 'application/ld+json; profile="' . Context::ACTIVITY_STREAMS . '"',
                'User-Agent' => config('activitypub.user-agent'),
            ]
        );

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withHeaders($headers)->post($this->actor->inbox, $request);
        if ($response->failed()) {
            Log::warning('Request failed. Response:', [$response]);
            $response->throw();
        }

        return $response;
    }
}
