<?php

namespace App\Http\Controllers\ActivityPub\Instance;

use ActivityPhp\Type;
use App\Http\Controllers\Controller;
use App\Jobs\ActivityPub\ProcessCreateAction;
use App\Models\ActivityPub\Activity;
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
     * @return never
     */
    public function __invoke(Request $request)
    {
        // info(__CLASS__, ['request' => $request]);
        /** @type \Symfony\Component\HttpFoundation\ParameterBag $action */
        $action = $request->json();

        // // Remove the actor from session
        $actor = $request->actorModel;
        $action->remove('actorModel');

        $type = $action->get('type');
        // $target = $this->tryToFindTarget($action);
        // $objectType = data_get($action->get('object'), 'type');
        // // store the action
        // $activityModel = Activity::updateOrCreate([
        //     'activityId' => $action->get('id'),
        //     'type' => $type,
        // ], [
        //     'object' => $action->all(),
        //     'object_type' => $objectType,
        //     'target_id' => $target->id,
        //     'actor_id' => $actor->id,
        // ]);

        // Go ahead, process it
        $activityStream = Type::create($type, $action->all());
        switch ($type) {
            case 'Create':
                /** @var \App\Domain\ActivityPub\Mastodon\Create $activityStream */
                ProcessCreateAction::dispatch($activityStream);
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

                // case 'Story:Reaction':
                // 	// $this->handleStoryReactionActivity();
                // 	break;

                // case 'Story:Reply':
                // 	// $this->handleStoryReplyActivity();
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
