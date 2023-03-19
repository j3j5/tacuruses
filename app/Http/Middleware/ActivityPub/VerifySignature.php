<?php

namespace App\Http\Middleware\ActivityPub;

use App\Jobs\ActivityPub\GetActorByKeyId;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use function Safe\base64_decode;
use function Safe\openssl_verify;
use function Safe\preg_match;

class VerifySignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        Log::debug('Validating signature for', ['request' => $request]);

        if (!$request->hasHeader('Signature')) {
            $errorMsg = 'No signature found';
            Log::debug($errorMsg, ['headers' => $request->headers]);
            abort(401, $errorMsg);
        }

        // // See https://docs.joinmastodon.org/spec/security/#http-verify
        $signature = $request->header('Signature');
        if (!is_string($signature)) {
            $errorMsg = 'Multiple signatures found';
            Log::debug($errorMsg, [
                'headers' => $request->headers,
                'signature' => $signature,
            ]);
            abort(401, $errorMsg);
        }

        if (!$request->hasHeader('Date')) {
            Log::warning('No date present on header while validating signature. Aborting in prod.');
            abort_if(app()->environment('production'), 403, 'Missing date');
        }

        $date = $request->header('Date');
        if (Carbon::parse($date)->diffInHours() > 2) {
            Log::warning('Given date differs with current date too much. Aborting in prod.', ['given' => $date, 'current' => now()->toDateTimeString()]);
            abort_if(app()->environment('production'), 403, 'Missing date');
        }

        // 1. Split Signature: into its separate parameters.
        $parts = explode(',', $signature);
        if (!is_array($parts)) {
            Log::warning('The signature is not well formed. Aborting in prod.', ['signature' => $signature]);
            abort_if(app()->environment('production'), 403, 'Wrong Signature');
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
            abort_if(app()->environment('production'), 403, 'Wrong Signature');
        }

        // 2. Construct the signature string from the value of headers.
        $sigHeadersNames = explode(' ', $sigParameters['headers']);

        $headers = [];
        foreach ($sigHeadersNames as $header) {
            switch($header) {
                case '(request-target)':
                    $headers[$header] = 'post /' . $request->path();
                    break;
                case 'digest':
                    $digest = 'SHA-256=' . base64_encode(hash('sha256', $request->getContent(), true));
                    $headerDigest = $request->header('digest');
                    if ($digest !== $headerDigest) {
                        Log::notice('Digest does not match. Aborting in prod.', ['given' => $headerDigest, 'calculated' => $digest]);
                        abort_if(app()->environment('production'), 403, 'Digest does not match');
                    }
                    $headers[$header] = $digest;
                    break;
                default:
                    $headers[$header] = $request->header($header);
                    break;
            }
        }

        $stringToBeSigned = collect($headers)
            ->map(fn ($value, $name) => mb_strtolower($name) . ': ' . $value)
            ->implode("\n");

        // 3. Fetch the keyId and resolve to an actor’s publicKey.
        /** @var \App\Models\ActivityPub\Actor $actor */
        $actor = GetActorByKeyId::dispatchSync($sigParameters['keyId']);
        $publicKey = $actor->publicKey;

        // Verify the actor's public key is the same than the action's actor
        if (Arr::get($request->toArray(), 'actor') !== $actor->activityId) {
            Log::warning('Actor\'s key and actor on action do not match. Aborting in prod', [
                'actor' => $actor,
                'request' => $request->toArray(),
            ]);
            abort_if(app()->environment('production'), 403, 'Actors do not match');
        }

        Log::debug('Algorithm is ' . $sigParameters['algorithm']);
        $algo = match ($sigParameters['algorithm']) {
            // @see https://www.php.net/manual/en/openssl.signature-algos.php
            'rsa-sha256' => OPENSSL_ALGO_SHA256,
            default => OPENSSL_ALGO_SHA256,
        };

        $verified = openssl_verify(
            $stringToBeSigned,
            base64_decode($sigParameters['signature']),
            $publicKey,
            $algo
        );

        if ($verified !== 1) {
            Log::warning('Unable to verify given signature');
            abort_if(app()->environment('production'), 403, 'Unable to verify given signature');
        }

        Log::debug('Signature is VERIFIED!');

        $request->merge(['actorModel' => $actor]);

        return $next($request);
    }
}
