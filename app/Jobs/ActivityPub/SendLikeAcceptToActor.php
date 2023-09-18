<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type;
use App\Models\ActivityPub\ActivityLike;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\RemoteActor;
use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
use App\Traits\SendsSignedRequests;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendLikeAcceptToActor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use SendsSignedRequests;

    private readonly RemoteActor $actor;
    private readonly LocalNote $target;
    private readonly LocalActor $targetActor;
    private readonly ActivityLike $like;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RemoteActor $actor, LocalNote $target, ActivityLike $like)
    {
        $this->actor = $actor;
        $this->target = $target;
        $this->targetActor = $target->actor;
        $this->like = $like;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Signer $signer)
    {
        $accept = Type::create('Accept', [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->target->activityId . '#accepts/likes/' . $this->like->slug,
            'actor' => $this->target->activityId,
            'object' => [
                'id' => $this->like->activityId,
                'actor' => $this->actor->activityId,
                'type' => 'Like',
                'object' => $this->target->activityId,
            ],
        ])->toArray();
        $this->sendSignedPostRequest(
            signer: $signer,
            actorSigning: $this->targetActor,
            data: $accept,
            url: $this->actor->inbox,
        );

        $this->like->markAsAccepted();
    }
}
