<?php

namespace App\Jobs\ActivityPub;

use App\Models\ActivityPub\RemoteActor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 *
 * @phpstan-type InstanceUser array{id: string, type: string, preferredUsername: string, name: string, summary: ?string, url: string, icon:array<string,string>, image: array<string,string>, inbox: string, endpoints: array<string, string>, publicKey: array<string, string> }
 */
class FindActorInfo
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private readonly string $actorId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle() : RemoteActor
    {
        info('finding actor');
        try {
            return RemoteActor::where(['activityId' => $this->actorId])->firstOrFail();
        } catch (ModelNotFoundException) {
        }

        // Retrieve actor info from instance and store it on the DB
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::acceptJson()->get($this->actorId);
        $actorData = $response->throw()->json();
        info('actorData', [$actorData]);
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

        /** @var InstanceUser $data */
        $data = $validator->validate();

        $actor = RemoteActor::firstOrNew(['activityId' => $data['id']]);
        $actor->updateFromInstanceData($data);
        Log::debug('actor updated');
        return $actor;
    }
}
