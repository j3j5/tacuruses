<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use App\Exceptions\FederationDeliveryException;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\RemoteActor;
use App\Services\ActivityPub\Signer;
use App\Traits\SendsSignedRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Response;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 *
 * @phpstan-type InstanceUser array{id: string, type: string, preferredUsername: string, name: string, summary: ?string, url: string, icon: array<string,string>, image: array<string,string>, inbox: string, outbox: string, following: string, followers: string, endpoints: array<string,string>, publicKey: array<string,string> }
 */
final class FindActorInfo
{
    use Dispatchable, SerializesModels;
    use SendsSignedRequests;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private readonly string $actorId,
        private readonly bool $tryLocal = true,
    ) {
        Validator::validate(
            data: ['actorId' => $actorId],
            rules: ['actorId' => 'required|url']
        );
    }

    /**
     * Execute the job.
     *
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function handle(Signer $signer) : Actor
    {
        Log::debug('finding actor: ' . $this->actorId);
        if ($this->tryLocal) {
            try {
                Log::debug('Actor found, stop checking remotely');
                return Actor::where(['activityId' => $this->actorId])->firstOrFail();
            } catch (ModelNotFoundException) {
            }
        }

        // Retrieve actor info from instance and store it on the DB
        try {
            $response = $this->sendSignedGetRequest(
                signer: $signer,
                url: $this->actorId,
            );
        } catch (FederationDeliveryException $e) {
            $response = $e->response;
        }

        if ($response->failed()) {
            Log::info($this->actorId . ' could not be retrieved', [
                'code' => $response->status(),
                'response' => $response->body(),
            ]);
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Actor cannot be found');
        }

        $actorData = Arr::wrap($response->json());
        $validator = Validator::make($actorData, [
            'id' => ['required', 'string'],
            'type' => ['required', 'string'],
            'preferredUsername' => ['required', 'string'],
            'name' => ['required', 'string'],
            'summary' => ['string'],
            'url' => ['required', 'string', 'url'],
            'icon.url' => ['string', 'url'],
            'image.url' => ['string', 'url'],
            'inbox' => ['required', 'string', 'url'],
            'endpoints.sharedInbox' => ['required', 'string', 'url'],
            'publicKey.id' => ['required', 'string', 'url'],
            'publicKey.publicKeyPem' => ['required', 'string'],
        ]);

        /** @phpstan-var InstanceUser $data */
        $data = $validator->validate();

        /** @var \App\Models\ActivityPub\RemoteActor $actor */
        $actor = RemoteActor::firstOrNew(['activityId' => $data['id']]);
        $actor->updateFromInstanceData($data);
        return $actor;
    }
}
