<?php

namespace App\Http\Controllers\ActivityPub\Instance;

use ActivityPhp\Type;
use App\Http\Controllers\Controller;
use App\Http\Middleware\OnlyContentType;
use App\Jobs\ActivityPub\ProcessCreateAction;
use App\Jobs\ActivityPub\ProcessDeleteAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        $activityStream = Type::create($type, $action->all());

        // TODO: Add proper authorization to check that the verified actor who
        // signed the activity stream can indeed perform whatever action they're
        // trying perform (create/update/delete a note from themselves...)

        switch ($type) {
            case 'Create':
                /** @var \App\Domain\ActivityPub\Mastodon\Create $activityStream */
                ProcessCreateAction::dispatch($actor, $activityStream);
                break;
            case 'Delete':
                /** @var \ActivityPhp\Type\Extended\Activity\Delete $activityStream */
                ProcessDeleteAction::dispatch($activityStream);
                break;
                // case 'Update':
                // 	(new UpdateActivity($this->payload, $this->profile))->handle();
                // 	break;
                // case 'Undo':
                // break;

                // case 'View':
                // break;

                // case 'Reject':
                // break;

                // case 'Delete':
                // break;

                // case 'Add':
                // 	break;

                // case 'Accept':
                // 	break;

            default:
                Log::warning('Unknown verb on inbox', ['class' => __CLASS__, 'payload' => $action]);
                abort(422, 'Unknow type of action');
        }

        return response()->activityJson([], Response::HTTP_ACCEPTED);
    }
}
