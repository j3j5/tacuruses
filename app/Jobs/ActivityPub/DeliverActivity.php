<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type\Core\Activity;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Signer;
use App\Traits\SendsSignedRequests;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

use function Safe\parse_url;

final class DeliverActivity extends BaseFederationJob implements ShouldQueue, ShouldBeUnique
{
    use SendsSignedRequests;

    private readonly string $instance;

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

        $this->instance = (string) (parse_url($this->inbox, PHP_URL_HOST) ?? $this->inbox); // @phpstan-ignore cast.string
        Context::add('toInstance', $this->instance);
        Context::add('actorSigning', $this->actor->id);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Signer $signer): void
    {
        $response = $this->sendSignedPostRequest(
            signer: $signer,
            actorSigning: $this->actor,
            url: $this->inbox,
            data: $this->activity->toArray(),
        );

        Log::debug('Delivered activity; response ' . $response->status(), [
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

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'instance:' . $this->instance,
            'signing:' . $this->actor->id,
            'delivery',
            'federation-out',
        ];
    }
}
