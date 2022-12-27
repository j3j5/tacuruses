<?php

namespace App\Http\Middleware\ActivityPub;

use App\Jobs\ActivityPub\GetPublicKeyForActor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ValidateSignature
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

        // See https://docs.joinmastodon.org/spec/security/#http-verify
        $signature = $request->header('Signature');

        // 1. Split Signature: into its separate parameters.
        $parts = explode(',', $signature);
        if (!is_array($parts)) {
            Log::notice('The signature is not well formed', ['signature' => $signature]);
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
            Log::notice('The signature seems to be missing parts', ['sigParameters' => $sigParameters]);
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
                        Log::notice('Digest does not match', ['given' => $headerDigest, 'calculated' => $digest]);
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
        $publicKey = GetPublicKeyForActor::dispatchSync($sigParameters['keyId']);
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

        if (!$verified) {
            Log::warning('Unable to verify given signature');
            abort_if(app()->environment('production'), 403, 'Unable to verify given signature');
        }

        return $next($request);
    }
}
