<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type\Extended\Activity\Delete;
use App\Models\ActivityPub\RemoteActor;
use App\Models\ActivityPub\RemoteNote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

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
        if (is_string($this->action->object)) {
            $this->deleteActor();
            return;
        }
        $this->deleteNote();
    }

    private function deleteActor() : void
    {
        if ($this->action->actor !== $this->action->object) {
            throw new RuntimeException('Unsupported Delete Actor action: ' . $this->action->toJson());
        }
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::acceptJson()->get($this->action->object);
        if (!in_array($response->status(), [Response::HTTP_GONE, Response::HTTP_NOT_FOUND])) {
            Log::info($this->action->object . ' does not seems to be gone, skipping deletion', [
                'code' => $response->status(),
                'response' => $response->body(),
            ]);
            return;
        }
        Log::debug('deleting ' . $this->action->object . ' from DB');

        RemoteActor::where('activityId', $this->action->object)->delete();
    }

    private function deleteNote() : void
    {
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::acceptJson()->get($this->action->object->id);
        if (!in_array($response->status(), [Response::HTTP_GONE, Response::HTTP_NOT_FOUND])) {
            Log::info($this->action->object->id . ' does not seems to be gone, skipping deletion', [
                'code' => $response->status(),
                'response' => $response->body(),
            ]);
            return;
        }
        Log::debug('deleting ' . $this->action->object->id . ' from DB');

        RemoteNote::where('activityId', $this->action->object->id)->delete();
    }
}
