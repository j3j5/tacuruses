<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Context;
use App\Services\ActivityPub\Signer;
use App\Traits\SendsSignedRequests;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;

use function Safe\json_decode;
use function Safe\json_encode;

final class SendDeleteActorToInstance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use SendsSignedRequests;

    private readonly LocalActor $actor;
    private readonly string $inbox;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(LocalActor $actor, string $inbox)
    {
        $this->actor = $actor;
        $this->inbox = $inbox;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Signer $signer)
    {
        $delete = Type::create('Delete', [
            '@context' => Context::ACTIVITY_STREAMS,
            'id' => $this->actor->activityId . '#delete',
            'actor' => $this->actor->activityId,
            'to' => [
                Context::ACTIVITY_STREAMS_PUBLIC,
            ],
            'object' => $this->actor->activityId,
        ])->toArray();
        $response = $this->sendSignedPostRequest(
            signer: $signer,
            actorSigning: $this->actor,
            url: $this->inbox,
            data: $delete,
            middlewares: [Middleware::mapRequest(function (RequestInterface $request) : RequestInterface {
                // Add the signature parameter to the JSON request
                $signature = $request->getHeaderLine('Signature');
                $body = json_decode((string) $request->getBody(), true);
                $body['signature'] = [
                    'type' => 'RsaSignature2017',
                    'creator' => $this->actor->key_id,
                    'signatureValue' => $signature,
                ];
                /** @var \GuzzleHttp\Psr7\HttpFactory $httpFactory */
                $httpFactory = app(HttpFactory::class);
                $request->withBody($httpFactory->createStream(json_encode($body)));

                return $request;
            }),]
        );

        Log::debug('status: ' . $response->status() . PHP_EOL . 'response: ' . $response->body());
    }
}
