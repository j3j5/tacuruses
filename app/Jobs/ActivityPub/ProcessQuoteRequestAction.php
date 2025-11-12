<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use App\Domain\ActivityPub\Mastodon\QuoteRequest;
use App\Models\ActivityPub\ActivityQuoteRequest;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\Quote;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;
use Webmozart\Assert\Assert;

final class ProcessQuoteRequestAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private readonly QuoteRequest $action,
        private readonly ActivityQuoteRequest $activity
    ) {
        Context::add('actor', $this->activity->actor_id);
        Context::add('target', $this->activity->target_id);
        Context::add('type', $this->activity->type);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $actor = $this->activity->actor;
        $target = $this->activity->target;
        Assert::isInstanceOf($this->activity->target, LocalNote::class);

        $actor = $this->activity->actor;
        $target = $this->activity->target;

        // Store the share
        Quote::updateOrCreate(
            [
                'activityId' => $this->action->get('id'),
            ],
            [
                'actor_id' => $actor->id,
                'target_id' => $target->id,
                'quote' => $this->action->get('instrument')->toArray(),
            ]
        );

        if ($actor instanceof RemoteActor) {
            // Send the accept back
            SendQuoteRequestAcceptToActor::dispatch($actor, $target, $this->activity);
            return;
        }

        $this->activity->markAsAccepted();

    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'instance-origin:' . $this->activity->actor->domain,
            'target-actor:' . $this->activity->target?->actor_id,
            'accept',
            'federation-in',
        ];
    }
}
