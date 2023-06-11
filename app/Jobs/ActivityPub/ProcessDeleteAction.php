<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type\Extended\Activity\Delete;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessDeleteAction implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected readonly Delete $action)
    {
        //
    }

    public function handle() : void
    {
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::acceptJson()->get($this->action->actor);
        if ($response->status() !== 410) {
            Log::info($this->action->actor . ' does not seems to be gone, skipping deletion', [
                'code' => $response->status(),
                'response' => $response->body(),
            ]);
            return;
        }
        Log::debug('deleting ' . $this->action->actor . ' from DB');
        RemoteActor::where('activityId', $this->action->actor)->delete();
    }
}
