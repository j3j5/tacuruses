<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Signer;
use App\Traits\SendsSignedRequests;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;

use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\parse_url;

final class SendUpdateToInstance extends BaseFederationJob implements ShouldQueue
{
    use SendsSignedRequests;

    private readonly string $instance;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private readonly LocalActor $actor, private readonly string $inbox)
    {
        $this->instance = (string) (parse_url($this->inbox, PHP_URL_HOST) ?? $this->inbox);  // @phpstan-ignore cast.string
        Context::add('toInstance', $this->instance);
        Context::add('actorSigning', $this->actor->id);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Signer $signer): void
    {
        $update = $this->actor->getAPUpdate()->toArray();

        $response = $this->sendSignedPostRequest(
            signer: $signer,
            actorSigning: $this->actor,
            url: $this->inbox,
            data: $update,
            middlewares: [Middleware::mapRequest(function (RequestInterface $request) : RequestInterface {
                // Add the signature parameter to the JSON request
                $signature = $request->getHeaderLine('Signature');
                $body = json_decode((string) $request->getBody(), true);
                $body['signature'] = [
                    'type' => 'RsaSignature2017',
                    'creator' => $this->actor->publicKeyId,
                    'signatureValue' => $signature,
                ];
                /** @var \GuzzleHttp\Psr7\HttpFactory $httpFactory */
                $httpFactory = app(HttpFactory::class);
                $request->withBody($httpFactory->createStream(json_encode($body)));
                Log::debug('request: ', $body);
                return $request;
            }),]
        );

        Log::debug('update sent; status: ' . $response->status() . PHP_EOL . 'response: ' . $response->body());
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'federation-out',
            'update',
            'instance:' . $this->instance,
            'signing:' . $this->actor->id,
        ];
    }
}
