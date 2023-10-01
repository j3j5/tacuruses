<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use App\Models\ActivityPub\ActivityAccept;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\Follow;
use App\Models\ActivityPub\LocalActor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class ProcessAcceptAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private readonly ActivityAccept $activity
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $actor = $this->activity->actor;
        $target = $this->activity->target;
        if (!$actor instanceof LocalActor || !$target instanceof Actor) {
            Log::notice('Accept activity looks wrong', [$this->activity]);
            throw new RuntimeException('The ActivityAccept does not seem to have a valid actor or target');
        }
        // TODO: implement other types besides follow
        $follow = Follow::where('actor_id', $actor->id)
            ->where('target_id', $target->id)
            ->firstOrFail();
        $this->activity->markAsAccepted();
        $follow->accept();
    }
}
