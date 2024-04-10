<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use App\Models\ActivityPub\Actor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Validator;

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
        Validator::validate(['keyId' => $keyId], ['keyId' => 'required|url']);
    }

    /**
     * Execute the job.
     */
    public function handle() : Actor
    {
        try {
            // Try to find the actor on the DB by publicKeyId (FindActorInfo tries on activityId)
            return Actor::where('publicKeyId', $this->keyId)->firstOrFail();
        } catch (ModelNotFoundException) {
        }

        // We already tried for publicKeyId, so not an issue that it won't match the activityId
        $actor = FindActorInfo::dispatchSync($this->keyId, false);

        return $actor;
    }

}
