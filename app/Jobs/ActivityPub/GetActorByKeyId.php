<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use App\Models\ActivityPub\Actor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;

final class GetActorByKeyId
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
     */
    public function handle() : Actor
    {
        try {
            return $this->findLocalKey();
        } catch (ModelNotFoundException) {
        }

        // We already tried for publicKeyId, so not an issue that it won't match
        $actor = FindActorInfo::dispatchSync($this->keyId, false);

        return $actor;
    }

    private function findLocalKey() : Actor
    {
        return Actor::where('publicKeyId', $this->keyId)->firstOrFail();
    }
}
