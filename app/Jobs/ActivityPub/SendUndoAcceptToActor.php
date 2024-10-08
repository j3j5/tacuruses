<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type;
use App\Models\ActivityPub\ActivityUndo;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Services\ActivityPub\Context as ActivityPubContext;
use App\Services\ActivityPub\Signer;
use App\Traits\SendsSignedRequests;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Context;

use function Safe\parse_url;

final class SendUndoAcceptToActor extends BaseFederationJob implements ShouldQueue
{
    use SendsSignedRequests;

    private readonly LocalActor $targetActor;
    private readonly string $instance;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly LocalActor|LocalNote $target,
        private readonly ActivityUndo $undo
    ) {
        $this->targetActor = $target instanceof LocalNote ?
            $target->actor :
            $target;

        $this->instance = (string) (parse_url($this->actor->inbox, PHP_URL_HOST) ?? $this->actor->inbox);  // @phpstan-ignore cast.string
        Context::add('toInstance', $this->instance);
        Context::add('actorSigning', $this->targetActor->id);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Signer $signer): void
    {
        $accept = Type::create('Accept', [
            '@context' => ActivityPubContext::ACTIVITY_STREAMS,
            'id' => $this->target->activityId . '#accepts/undo/' . $this->undo->slug,
            'actor' => $this->target->activityId,
            'object' => [
                'id' => $this->undo->activityId,
                'actor' => $this->actor->activityId,
                'type' => 'Undo',
                'object' => $this->target->activityId,
            ],
        ])->toArray();

        $this->sendSignedPostRequest(
            signer: $signer,
            actorSigning: $this->targetActor,
            data: $accept,
            url: $this->actor->inbox,
        );

        $this->undo->markAsAccepted();
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
            'signing:' . $this->targetActor->id,
            'accept',
            'federation-out',
        ];
    }
}
