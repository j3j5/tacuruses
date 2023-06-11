<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type\Core\Activity;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Signer;
use App\Traits\SendsSignedRequests;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class DeliverActivity implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
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
        $response = $this->sendSignedRequest(
            signer: $signer,
            request: $this->activity->toArray(),
            inbox: $this->inbox,
            actorSigning: $this->actor,
        );
        Log::debug('Delivering activity; response ' . $response->status());
    }
}
