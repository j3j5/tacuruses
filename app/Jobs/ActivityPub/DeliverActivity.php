<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type\Core\Activity;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Signer;
use App\Traits\SendsSignedRequests;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class DeliverActivity extends BaseFederationJob implements ShouldQueue, ShouldBeUnique
{
    use SendsSignedRequests;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private readonly LocalActor $actor,
        private readonly Activity $activity,
        private readonly string $inbox,
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Signer $signer)
    {
        $response = $this->sendSignedPostRequest(
            signer: $signer,
            actorSigning: $this->actor,
            url: $this->inbox,
            data: $this->activity->toArray(),
        );
        Log::debug('Delivering activity; response ' . $response->status(), [
            'activity' => $this->activity->toArray(),
            'inbox' => $this->inbox,
        ]);
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->activity->id . '|' . $this->inbox;
    }
}
