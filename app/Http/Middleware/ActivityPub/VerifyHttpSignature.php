<?php

declare(strict_types=1);

namespace App\Http\Middleware\ActivityPub;

use ActivityPhp\Type;
use App\Enums\ActivityTypes;
use App\Jobs\ActivityPub\FindActorInfo;
use App\Jobs\ActivityPub\GetActorByKeyId;
use App\Jobs\ActivityPub\ProcessDeleteAction;
use App\Services\ActivityPub\Verifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use function Safe\preg_match;

use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class VerifyHttpSignature
{

    public function __construct(private Verifier $verifier)
    {
        //
    }

    /**
     * Handle an incoming request.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        Log::debug('Validating signature for', ['request' => $request]);

        // For delete activities, the signature doesn't really matter, we'll check
        // later on the job whether the user actually exists on its activityID location
        // and act based on that, we don't really care who is notifying us about it
        if ($request->json('type') === ActivityTypes::DELETE->value && is_string($request->json('object'))) {
            /** @phpstan-ignore-next-line */
            ProcessDeleteAction::dispatch(Type::create('Delete', $request->json()->all()));
            return response()->activityJson();
        }

        if (!$request->hasHeader('Signature')) {
            $errorMsg = 'Missing signature';
            Log::debug($errorMsg, ['headers' => $request->headers]);
            abort(Response::HTTP_UNAUTHORIZED, $errorMsg);
        }

        $signature = $request->header('Signature');
        if ($signature === null) {
            $errorMsg = 'Missing signature 2';
            Log::debug($errorMsg, ['headers' => $request->headers]);
            abort(Response::HTTP_UNAUTHORIZED, $errorMsg);
        }

        if (!$request->hasHeader('Date')) {
            Log::warning('No date present on header while validating signature. Aborting in prod.');
            abort_if(app()->environment(['production', 'testing']), Response::HTTP_UNAUTHORIZED, 'Missing date');
        }

        $keyIdRegex = '/keyId="(?<keyId>.+?)",/';
        if (!preg_match($keyIdRegex, $signature, $sigParameters)) {
            Log::warning('Unable to find keyId on given signature', ['signature' => $signature]);
            abort_if(app()->environment(['production', 'testing']), Response::HTTP_UNAUTHORIZED, 'Wrong signature format');
        }
        /** @var \App\Models\ActivityPub\Actor $actor */
        $actor = GetActorByKeyId::dispatchSync($sigParameters['keyId']);

        // Verify the actor's public key is the same than the action's actor
        if (Arr::get($request->toArray(), 'actor') !== $actor->activityId) {
            Log::warning("Actor's key and actor on action do not match. Aborting in prod", [
                'actor' => $actor,
                'request' => $request->toArray(),
            ]);
            abort_if(app()->environment(['production', 'testing']), Response::HTTP_UNAUTHORIZED, 'Actors do not match');
        }

        $verified = false;
        $psrRequest = app(PsrHttpFactory::class)->createRequest($request);
        try {
            $verified = $this->verifier->verifyRequest($psrRequest, $actor->public_key_object);
        } catch (RuntimeException $e) {
            Log::warning($e->getMessage());
            abort_if(app()->environment(['production', 'testing']), Response::HTTP_UNAUTHORIZED, $e->getMessage());
        }

        if (!$verified) {
            Log::warning('Unable to verify given signature, try refetching user\'s public key');
            // Enable blind key rotation, the author's key may have been locally cached
            // and changed on the remote server, so let's try to retrieve it again from
            // the remote server and try again.
            $actor = FindActorInfo::dispatchSync($sigParameters['keyId'], false);

            try {
                $verified = $this->verifier->verifyRequest($psrRequest, $actor->public_key_object);
            } catch (RuntimeException $e) {
                Log::warning($e->getMessage());
                abort_if(app()->environment(['production', 'testing']), Response::HTTP_UNAUTHORIZED, $e->getMessage());
            }

            if (!$verified) {
                abort_if(app()->environment(['production', 'testing']), Response::HTTP_UNAUTHORIZED, 'Unable to verify given signature');
            }
        }

        Log::debug('Signature is VERIFIED!');

        $request->merge(['actorModel' => $actor]);

        return $next($request);
    }
}
