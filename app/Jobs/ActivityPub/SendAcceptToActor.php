<?php

namespace App\Jobs\ActivityPub;

use App\Models\ActivityPub\Follow;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\RemoteActor;
use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendAcceptToActor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private readonly RemoteActor $actor;
    private readonly LocalActor $target;
    private readonly Follow $follow;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RemoteActor $actor, LocalActor $target, Follow $follow)
    {
        $this->actor = $actor;
        $this->target = $target;
        $this->follow = $follow;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $accept = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $this->target->activityId . '#accepts/follows/' . $this->follow->slug,
            'type' => 'Accept',
            'actor' => $this->target->activityId,
            'object' => [
                'id' => $this->follow->remote_id,
                'actor' => $this->actor->activityId,
                'type' => 'Follow',
                'object' => $this->target->activityId,
            ],
        ];

        $body = json_encode($accept, JSON_THROW_ON_ERROR);
        // Make HTTP post back to the server of the actor
        /** @var \App\Services\ActivityPub\Signer $signer */
        $signer = app(Signer::class);
        $headers = $signer
            ->sign(
                $this->target,
                $this->actor->inbox,
                $body,
                [
                    'Content-Type' => 'application/ld+json; profile="' . Context::ACTIVITY_STREAMS . '"',
                    'User-Agent' => config('activitypub.user-agent'),
                ]
            );
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withHeaders($headers)->post($this->actor->inbox, $accept);
        if ($response->failed()) {
            Log::warning('response', [$response]);
        }
    }
}
