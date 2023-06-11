<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 *
 * @phpstan-type InstanceUser array{id: string, type: string, preferredUsername: string, name: string, summary: ?string, url: string, icon: array<string,string>, image: array<string,string>, inbox: string, outbox: string, following: string, followers: string, endpoints: array<string,string>, publicKey: array<string,string> }
 */
final class FindActorInfo
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private readonly string $actorId, private readonly bool $tryLocal = true)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @throws \Http\Client\Exception\RequestException
     */
    public function handle() : Actor
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
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::acceptJson()->get($this->actorId);
        if ($response->failed()) {
            Log::info($this->actorId . ' could not be retrieved', [
                'code' => $response->status(),
                'response' => $response->body(),
            ]);
            abort(201, 'Actor cannot be found, keep moving');
        }
        $actorData = $response->json();
        $validator = Validator::make($actorData, [
            'id' => ['required', 'string'],
            'type' => ['required', 'string'],
            'preferredUsername' => ['required', 'string'],
            'name' => ['string'],
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
