<?php

namespace App\Jobs\ActivityPub;

use App\Models\ActivityPub\RemoteActor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GetPublicKeyForActor
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
    public function handle() : string
    {
        try {
            return $this->findLocalKey();
        } catch (ModelNotFoundException) {
        }

        $publicKeyData = Http::acceptJson()->get($this->keyId)->throw()->json('publicKey');

        if (!isset($publicKeyData['id']) || $publicKeyData['id'] !== $this->keyId) {
            // TODO: user own exception
            throw new RuntimeException('ids for keys don\'t match!!');
        }

        if (!isset($publicKeyData['publicKeyPem'])) {
            // TODO: user own exception
            throw new RuntimeException('no pem cert found on response');
        }

        return $publicKeyData['publicKeyPem'];
    }

    private function findLocalKey() : string
    {
        return RemoteActor::where('publicKeyId', $this->keyId)->firstOrFail('publicKey')->publicKey;
    }
}
