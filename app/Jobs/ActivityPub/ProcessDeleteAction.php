<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type\Core\Link;
use ActivityPhp\Type\Extended\AbstractActor;
use ActivityPhp\Type\Extended\Activity\Delete;
use ActivityPhp\Type\Extended\Object\Tombstone;
use App\Exceptions\FederationDeliveryException;
use App\Models\ActivityPub\RemoteActor;
use App\Models\ActivityPub\RemoteNote;
use App\Services\ActivityPub\Signer;
use App\Traits\SendsSignedRequests;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;

use function Safe\parse_url;

final class ProcessDeleteAction implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;
    use SendsSignedRequests;

    private Signer $signer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected readonly Delete $action)
    {
        //
    }

    public function handle(Signer $signer) : void
    {
        $this->signer = $signer;

        if (is_string($this->action->object)
            || $this->action->object instanceof Link
            || $this->action->object instanceof AbstractActor
        ) {
            $this->deleteActor();
            return;
        }
        if ($this->action->object instanceof Tombstone) {
            $this->deleteNote();
            return;
        }

        Log::warning('DELETE action not implemented yet', $this->action->toArray());
        throw new RuntimeException('This DELETE action format is not implemented yet');
    }

    private function deleteActor() : void
    {
        if ($this->action->actor !== $this->action->object) {
            throw new RuntimeException('Unsupported Delete Actor action: ' . $this->action->toJson());
        }

        if (is_string($this->action->object)) {
            $actorActivityId = $this->action->object;
        } elseif ($this->action->object instanceof Link) {
            $actorActivityId = (string) $this->action->object->href;
        } elseif ($this->action->object instanceof AbstractActor) {
            $actorActivityId = (string) $this->action->object->id;
        }

        try {
            $response = $this->sendSignedGetRequest(
                signer: $this->signer,
                url: $actorActivityId,
            );
        } catch (FederationDeliveryException $e) {
            $response = $e->response;
        }
        if (!in_array($response->status(), [Response::HTTP_GONE, Response::HTTP_NOT_FOUND])) {
            Log::debug($actorActivityId . ' does not seem to be gone, skipping ACTOR deletion', [
                'code' => $response->status(),
                'response' => $response->body(),
            ]);
            return;
        }
        Log::debug('deleting ' . $actorActivityId . ' from DB');

        RemoteActor::where('activityId', $actorActivityId)->delete();
    }

    private function deleteNote() : void
    {
        /** @var \ActivityPhp\Type\Extended\Object\Tombstone $object */
        $object = $this->action->object;

        try {
            $response = $this->sendSignedGetRequest(
                signer: $this->signer,
                url: $object->id,
            );
        } catch (FederationDeliveryException $e) {
            $response = $e->response;
        }

        if (!in_array($response->status(), [Response::HTTP_GONE, Response::HTTP_NOT_FOUND])) {
            Log::debug($object->id . ' does not seem to be gone, skipping NOTE deletion', [
                'code' => $response->status(),
                'response' => $response->body(),
            ]);
            return;
        }

        Log::debug('deleting ' . $object->id . ' from DB');

        RemoteNote::where('activityId', $object->id)->delete();
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return $this->action->id;
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        $tags = [
            'delete',
            'federation-in',
        ];
        $object = $this->action->object;
        if (is_string($object)) {
            $activityId = $object;
        } else {
            $activityId = (string) match(true) {
                $object instanceof AbstractActor => $object->id,
                $object instanceof Tombstone => $object->id,
                $object instanceof Link => $object->href,
                default => 'unknown',
            };
        }

        $domain = parse_url($activityId, PHP_URL_HOST);
        if (is_string($domain)) {
            $tags = Arr::prepend($tags, 'instance-origin:' . $domain);
        }
        $tags = Arr::prepend($tags, 'object:' . $activityId);

        return $tags;
    }
}
