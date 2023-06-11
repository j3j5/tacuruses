<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use App\Models\ActivityPub\ActivityCreate;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
use App\Traits\SendsSignedRequests;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendCreateAcceptToActor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use SendsSignedRequests;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private readonly LocalActor $actor, private ActivityCreate $create)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Signer $signer)
    {
        $accept = [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->create->target->activityId . '#accepts/create/' . $this->create->slug,
            'type' => 'Accept',
            'actor' => $this->create->target->actor->activityId,
            'object' => [
                'id' => $this->create->activityId,
                'actor' => $this->create->target->actor->activityId,
                'type' => 'Create',
                'object' => $this->create->target->activityId,
            ],
        ];
        $this->sendSignedRequest(
            signer: $signer,
            request: $accept,
            inbox: $this->create->target->actor->inbox,
            actorSigning: $this->actor,
        );

        $this->create->markAsAccepted();
    }
}
