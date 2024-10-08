<?php

declare(strict_types=1);

namespace App\Jobs\ActivityPub;

use ActivityPhp\Type;
use App\Models\ActivityPub\LocalNote;
use App\Services\ActivityPub\Context as ActivityPubContext;
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

final class SendDeleteNoteToInstance extends BaseFederationJob implements ShouldQueue
{
    use SendsSignedRequests;

    private readonly string $instance;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private readonly LocalNote $note, private readonly string $inbox)
    {
        $this->instance = (string) (parse_url($this->inbox, PHP_URL_HOST) ?? $this->inbox);  // @phpstan-ignore cast.string
        Context::add('toInstance', $this->instance);
        Context::add('actorSigning', $this->note->actor->id);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Signer $signer): void
    {
        $delete = Type::create('Delete', [
            '@context' => ActivityPubContext::ACTIVITY_STREAMS,
            'id' => $this->note->activityId . '#delete',
            'actor' => $this->note->actor->activityId,
            'to' => [
                ActivityPubContext::ACTIVITY_STREAMS_PUBLIC,
            ],
            'object' => Type::create('Tombstone', [
                'id' => $this->note->activityId,
            ])->toArray(),
        ])->toArray();

        $response = $this->sendSignedPostRequest(
            signer: $signer,
            actorSigning: $this->note->actor,
            url: $this->inbox,
            data: $delete,
            middlewares: [Middleware::mapRequest(function (RequestInterface $request) : RequestInterface {
                // Add the signature parameter to the JSON request
                $signature = $request->getHeaderLine('Signature');
                $body = json_decode((string) $request->getBody(), true);
                $body['signature'] = [
                    'type' => 'RsaSignature2017',
                    'creator' => $this->note->actor->publicKeyId,
                    'signatureValue' => $signature,
                ];
                /** @var \GuzzleHttp\Psr7\HttpFactory $httpFactory */
                $httpFactory = app(HttpFactory::class);
                $request->withBody($httpFactory->createStream(json_encode($body)));

                Log::debug('Sending DELETE note message to ' . $this->inbox, $body);

                return $request;
            }),]
        );

        Log::debug('delete note sent; status: ' . $response->status() . PHP_EOL . 'response: ' . $response->body());
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'instance:' . $this->instance,
            'signing:' . $this->note->actor->id,
            'delete',
            'federation-out',
        ];
    }
}
