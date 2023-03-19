<?php

namespace App\Http\Controllers\ActivityPub\Instance;

use ActivityPhp\Type;
use App\Http\Controllers\Controller;
use App\Jobs\ActivityPub\ProcessCreateAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SharedInboxController extends Controller
{
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
        // info(__CLASS__, ['request' => $request]);
        /** @type \Symfony\Component\HttpFoundation\ParameterBag $action */
        $action = $request->json();

        /** @var \App\Models\ActivityPub\Actor $actor */
        $actor = $request->actorModel;
        // // Remove the actor from request
        $action->remove('actorModel');

        /** @var string $type */
        $type = $action->get('type');

        // Go ahead, process it
        $activityStream = Type::create($type, $action->all());

        switch ($type) {
            case 'Create':
                /** @var \App\Domain\ActivityPub\Mastodon\Create $activityStream */
                ProcessCreateAction::dispatch($actor, $activityStream);
                break;
            case 'Update':
                // case 'Delete':
                // break;
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

                // case 'Update':
                // 	(new UpdateActivity($this->payload, $this->profile))->handle();
                // 	break;

            default:
                Log::warning('Unknown verb on inbox', ['class' => __CLASS__, 'payload' => $action]);
                abort(422, 'Unknow type of action');
        }

        return response()->activityJson();
    }
}
