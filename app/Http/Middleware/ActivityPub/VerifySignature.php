<?php

namespace App\Http\Middleware\ActivityPub;

use ActivityPhp\Type;
use App\Jobs\ActivityPub\GetActorByKeyId;
use App\Jobs\ActivityPub\ProcessDeleteAction;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PublicKey;
use RuntimeException;

use function Safe\base64_decode;
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
            $errorMsg = 'Missing signature';
            Log::debug($errorMsg, ['headers' => $request->headers]);
            abort(Response::HTTP_UNAUTHORIZED, $errorMsg);
        }

        $signature = $request->header('Signature');
        if (!is_string($signature)) {
            $errorMsg = 'Multiple signatures found';
            Log::debug($errorMsg, [
                'headers' => $request->headers,
                'signature' => $signature,
            ]);
            abort(Response::HTTP_UNAUTHORIZED, $errorMsg);
        }

        if (!$request->hasHeader('Date')) {
            Log::warning('No date present on header while validating signature. Aborting in prod.');
            abort_if(app()->environment(['production', 'testing']), Response::HTTP_UNAUTHORIZED, 'Missing date');
        }

        $date = $request->header('Date');

        // Only accept requests maximum 5 mins "from the future"
        if (Carbon::parse($date)->isFuture() && Carbon::parse($date)->diffInMinutes() > 5) {
            Log::warning('Given date is in the future, dates are way out of sync. Aborting in prod.', ['given' => $date, 'current' => now()->toDateTimeString()]);
            abort_if(app()->environment(['production', 'testing']), Response::HTTP_UNAUTHORIZED, 'Date is on the future');
        }

        // Check requests aren't older than 12 hours
        if (Carbon::parse($date)->diffInMinutes() > 12 * 60) {
            Log::warning('Given date is older than 12 hours. Aborting in prod.', ['given' => $date, 'current' => now()->toDateTimeString()]);
            abort_if(app()->environment(['production', 'testing']), Response::HTTP_UNAUTHORIZED, 'Request date is too old');
        }

        // For delete activities, the signature doesn't really matter, we'll check
        // later on the job whether the user actually exists on its activityID location
        // and act based on that, we don't really care who is notifying us about it
        if ($request->json('type') === 'Delete') {
            /** @phpstan-ignore-next-line */
            ProcessDeleteAction::dispatch(Type::create('Delete', $request->json()->all()));
            return response()->activityJson();
        }

        // See https://docs.joinmastodon.org/spec/security/#http-verify
        // 1. Split Signature: into its separate parameters.
        $parts = explode(',', $signature);
        if (!is_array($parts)) {
            Log::warning('The signature is not well formed. Aborting in prod.', ['signature' => $signature]);
            abort_if(app()->environment(['production', 'testing']), Response::HTTP_UNAUTHORIZED, 'Wrong signature 1');
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
            abort_if(app()->environment(['production', 'testing']), Response::HTTP_UNAUTHORIZED, 'Wrong signature 2');
        }

        // Calculate and compare the request's digest
        $digest = base64_encode(hash('sha256', $request->getContent(), true));
        $headerDigest = $request->header('digest');
        $arrayDigest = explode('=', $headerDigest, 2);
        if (!is_array($arrayDigest) || count($arrayDigest) !== 2) {
            Log::notice('Invalid digest. Aborting in prod.', ['given' => $headerDigest]);
            abort_if(app()->environment(['production', 'testing']), Response::HTTP_UNAUTHORIZED, 'Digest does not match');
        }
        [$hashFunction, $hash] = $arrayDigest;

        if (mb_strtolower($hashFunction) !== 'sha-256') {
            Log::notice('Invalid hash function used on digest.', ['given' => $headerDigest, 'calculated' => $digest]);
            abort_if(app()->environment(['production', 'testing']), Response::HTTP_UNAUTHORIZED, 'Invalid hash digest. Use SHA-256');
        }

        if ($hash !== $digest) {
            Log::notice('Digest does not match. Aborting in prod.', ['given' => $hash, 'calculated' => $digest]);
            abort_if(app()->environment(['production', 'testing']), Response::HTTP_UNAUTHORIZED, 'Digest does not match');
        }
        $digest = 'SHA-256=' . $digest;

        // 2. Construct the signature string from the value of headers.
        $sigHeadersNames = explode(' ', $sigParameters['headers']);

        $headers = [];
        foreach ($sigHeadersNames as $header) {
            switch($header) {
                case '(request-target)':
                    $headers[$header] = 'post /' . $request->path();
                    break;
                case 'digest':
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
            Log::warning("Actor's key and actor on action do not match. Aborting in prod", [
                'actor' => $actor,
                'request' => $request->toArray(),
            ]);
            abort_if(app()->environment(['production', 'testing']), Response::HTTP_UNAUTHORIZED, 'Actors do not match');
        }

        $algorithm = $sigParameters['algorithm'] ?? '';
        Log::debug('Algorithm is "' . $algorithm . '"');

        $key = PublicKeyLoader::load($publicKey);
        if (!$key instanceof PublicKey) {
            throw new RuntimeException('Public key does not seem valid');
        }
        $key = $key->withPadding(RSA::SIGNATURE_RELAXED_PKCS1);

        // Verify the calculated signature using the public key and the original signature
        if (!$key->verify($stringToBeSigned, base64_decode($sigParameters['signature']))) {
            Log::warning('Unable to verify given signature');
            abort_if(app()->environment(['production', 'testing']), Response::HTTP_UNAUTHORIZED, 'Unable to verify given signature');
        }

        Log::debug('Signature is VERIFIED!');

        $request->merge(['actorModel' => $actor]);

        return $next($request);
    }
}
