<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type\Extended\Activity\Announce;
use App\Models\ActivityPub\ActivityAnnounce;
use App\Models\ActivityPub\RemoteActor;
use App\Models\ActivityPub\Share;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;

final class ProcessAnnounceAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private readonly Announce $action,
        private readonly ActivityAnnounce $activity
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

        // Store the share
        Share::updateOrCreate(
            ['actor_id' => $actor->id, 'target_id' => $target->id],
            ['activityId' => $this->action->get('id')]
        );

        if ($actor instanceof RemoteActor) {
            // Send the accept back
            SendAnnounceAcceptToActor::dispatch($actor, $target, $this->activity);
        }
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
            'target-actor:' . $this->activity->target->actor_id,
            'target-note:' . $this->activity->target->id,
            'announce',
            'federation-in',
        ];
    }
}
