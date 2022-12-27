<?php

namespace App\Jobs\ActivityPub;

use App\DTO\ActivityPub\Follow;
use App\DTO\ActivityPub\Like;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessLikeAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private readonly Like $action)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // First or create the actor
        $actor = FindActorInfo::dispatchSync($this->action->actor);

        // Store the follow

        // Send the accept back
    }
}
