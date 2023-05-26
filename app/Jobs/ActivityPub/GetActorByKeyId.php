<?php

namespace App\Jobs\ActivityPub;

use App\Models\ActivityPub\Actor;
use Http\Client\Exception\RequestException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;

class GetActorByKeyId
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private readonly string $keyId)
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
        try {
            return $this->findLocalKey();
        } catch (ModelNotFoundException) {
        }

        try {
            // We already tried for publicKeyId, so not an issue that it won't match
            $actor = FindActorInfo::dispatchSync($this->keyId, false);
        } catch(RequestException $e) {
            throw $e;
        }

        return $actor;
    }

    private function findLocalKey() : Actor
    {
        return Actor::where('publicKeyId', $this->keyId)->firstOrFail();
    }
}
