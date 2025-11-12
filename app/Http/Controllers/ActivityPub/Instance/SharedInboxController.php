<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Instance;

use ActivityPhp\Type;
use App\Enums\ActivityTypes;
use App\Http\Controllers\ActivityPub\Actors\InboxController;
use App\Http\Controllers\Controller;
use App\Http\Middleware\OnlyContentType;
use App\Jobs\ActivityPub\ProcessCreateAction;
use App\Jobs\ActivityPub\ProcessDeleteAction;
use App\Jobs\ActivityPub\ProcessUpdateActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class SharedInboxController extends Controller
{

    public function __construct()
    {
        $this->middleware(OnlyContentType::class . ':application/activity+json');
    }

    /**
     *
     * @param \Illuminate\Http\Request $request
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request) : JsonResponse
    {
        /** @type \Symfony\Component\HttpFoundation\ParameterBag $action */
        $action = $request->json();

        /** @var \App\Models\ActivityPub\Actor $actor */
        $actor = $request->actorModel;
        // Remove the actor from request
        $action->remove('actorModel');

        /** @var string $type */
        $type = $action->get('type');
        Log::debug('Processing ' . $type . ' action from shared inbox');

        // Go ahead, process it
        $activityStream = Type::create($type, $request->input());

        // TODO: Add proper authorization to check that the verified actor who
        // signed the activity stream can indeed perform whatever action they're
        // trying perform (create/update/delete a note from themselves...)

        switch (ActivityTypes::tryFrom($type)) {
            case ActivityTypes::CREATE:
                /** @var \App\Domain\ActivityPub\Mastodon\Create $activityStream */
                ProcessCreateAction::dispatch($actor, $activityStream);
                break;
            case ActivityTypes::DELETE:
                /** @var \ActivityPhp\Type\Extended\Activity\Delete $activityStream */
                ProcessDeleteAction::dispatch($activityStream);
                break;
            case ActivityTypes::ANNOUNCE:
                // Put back the actor bc we're going to the actor's inbox controller
                $action->set('actorModel', $actor);
                App::call(InboxController::class, [$request]);
                break;
            case ActivityTypes::UPDATE:
                // TODO: review, it's the same as ProcessCreateActivity
                /** @var \ActivityPhp\Type\Extended\Activity\Update $activityStream */
                ProcessUpdateActivity::dispatch($actor, $activityStream);
                break;
                // 	(new UpdateActivity($this->payload, $this->profile))->handle();
                // 	break;
                // case ActivityTypes::UNDO:
                // break;

                // case ActivityTypes::VIEW:
                // break;

                // case ActivityTypes::REJECT:
                // break;

                // case ActivityTypes::DELETE:
                // break;

                // case ActivityTypes::ADD:
                // 	break;

                // case ActivityTypes::ACCEPT:
                // 	break;

            default:
                Log::warning("Unknown verb on inbox ($type)", ['payload' => $action]);
                abort(422, "Unknown type of action ($type)");
        }

        return response()->activityJson([], Response::HTTP_ACCEPTED);
    }
}
