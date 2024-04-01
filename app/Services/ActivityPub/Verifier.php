<?php

declare(strict_types=1);

namespace App\Services\ActivityPub;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use phpseclib3\Crypt\Common\PublicKey;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use function Safe\base64_decode;

use Safe\Exceptions\UrlException;
use function Safe\preg_match;

final class Verifier
{
    /**
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Carbon\Exceptions\InvalidFormatException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\HttpFoundation\Exception\BadRequestException
     * @throws \Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
     */
    public function verifyRequest(RequestInterface $request, PublicKey $key) : bool
    {
        $signature = $request->getHeaderLine('Signature');
        if (!is_string($signature)) {
            $errorMsg = 'Multiple signatures found';
            Log::debug($errorMsg, [
                'headers' => $request->getHeaders(),
                'signature' => $signature,
            ]);
            throw new RuntimeException($errorMsg);
        }

        $date = $request->getHeaderLine('Date');

        // Only accept requests maximum 5 mins "from the future"
        if (Carbon::parse($date)->isFuture() && Carbon::parse($date)->diffInMinutes(absolute: true) > 5) {
            Log::warning('Given date is in the future, dates are way out of sync. Aborting in prod.', ['given' => $date, 'current' => now()->toDateTimeString()]);
            throw new RuntimeException('Date is on the future');
        }

        // Check requests aren't older than 12 hours
        if (Carbon::parse($date)->diffInHours(absolute: true) > 12) {
            Log::warning('Given date is older than 12 hours. Aborting in prod.', ['given' => $date, 'current' => now()->toDateTimeString()]);
            throw new RuntimeException('Request date is too old');
        }

        // See https://docs.joinmastodon.org/spec/security/#http-verify
        // 1. Split Signature: into its separate parameters.
        $parts = explode(',', $signature);
        if (!is_array($parts)) {
            Log::warning('The signature is not well formed. Aborting in prod.', ['signature' => $signature]);
            throw new RuntimeException('Wrong signature 1');
        }
        $sigParameters = [];
        $pattern = '/(?<key>\w+)="(?<value>.+)"/';
        foreach ($parts as $part) {
            if (preg_match($pattern, $part, $matches)) {
                $sigParameters[$matches['key']] = $matches['value'];
            }
        }

        if (!isset($sigParameters['keyId'], $sigParameters['headers'], $sigParameters['signature'])) {
            Log::warning('The signature seems to be missing parts', ['sigParameters' => $sigParameters]);
            throw new RuntimeException('Wrong signature 2');
        }

        // Calculate and compare the request's digest
        $digest = base64_encode(hash('sha256', (string) $request->getBody(), true));
        $headerDigest = $request->getHeaderLine('Digest');
        $arrayDigest = explode('=', $headerDigest, 2);
        if (!is_array($arrayDigest) || count($arrayDigest) !== 2) {
            Log::notice('Invalid digest. Aborting in prod.', ['given' => $headerDigest]);
            throw new RuntimeException('Digest does not match');
        }
        [$hashFunction, $hash] = $arrayDigest;

        if (mb_strtolower($hashFunction) !== 'sha-256') {
            Log::notice('Invalid hash function used on digest.', ['given' => $headerDigest, 'calculated' => $digest]);
            throw new RuntimeException('Invalid hash digest. Use SHA-256');
        }

        if ($hash !== $digest) {
            Log::notice('Digest does not match. Aborting in prod.', ['given' => $hash, 'calculated' => $digest]);
            throw new RuntimeException('Digest does not match');
        }
        $digest = 'SHA-256=' . $digest;

        // 2. Construct the signature string from the value of headers.
        $sigHeadersNames = explode(' ', $sigParameters['headers']);
        $headers = [];
        foreach ($sigHeadersNames as $header) {
            switch($header) {
                case '(request-target)':
                    $headers[$header] = mb_strtolower($request->getMethod()) . ' ' . $request->getRequestTarget();
                    break;
                case 'digest':
                    $headers[$header] = $digest;
                    break;
                default:
                    $headers[$header] = $request->getHeaderLine($header);
                    break;
            }
        }

        $stringToBeSigned = collect($headers)
            ->map(fn ($value, $name) => mb_strtolower($name) . ': ' . $value)
            ->implode("\n");

        // 3. Fetch the keyId and resolve to an actor’s publicKey.

        $algorithm = $sigParameters['algorithm'] ?? '';
        Log::debug('Algorithm is "' . $algorithm . '"');

        try {
            $binarySignature = base64_decode($sigParameters['signature'], true);
        } catch (UrlException) {
            return false;
        }
        return $key->verify($stringToBeSigned, $binarySignature);
    }
}
