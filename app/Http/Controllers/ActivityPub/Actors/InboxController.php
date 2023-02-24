<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Domain\ActivityPub\Follow;
use App\Domain\ActivityPub\Like;
use App\Domain\ActivityPub\Undo;
use App\Http\Controllers\Controller;
use App\Jobs\ActivityPub\ProcessFollowAction;
use App\Jobs\ActivityPub\ProcessLikeAction;
use App\Jobs\ActivityPub\ProcessUndoAction;
use App\Models\ActivityPub\Activity;
use App\Models\ActivityPub\Actor;
use App\Models\ActivityPub\LocalActor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InboxController extends Controller
{
    /**
     *
     * @param \Illuminate\Http\Request $request
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Symfony\Component\HttpFoundation\Exception\BadRequestException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        info(__CLASS__, ['request' => $request]);

        /** @type \Symfony\Component\HttpFoundation\ParameterBag $action */
        $action = $request->json();
        $type = $action->get('type');

        $actorActivityId = $action->get('actor');
        $targetActivityId = $action->get('object');
        // For UNDO actions, the target_id is on object.object
        if (!is_string($targetActivityId)) {
            $targetActivityId = data_get($targetActivityId, 'object', '');
        }

        try {
            $target = LocalActor::where('activityId', $targetActivityId)->firstOrFail();
        } catch (ModelNotFoundException) {
            Log::debug("Unknown target, can't save the action", ['id' => $targetActivityId]);
            abort(404, 'Unknown target');
        }

        // store the action
        $actionModel = new Activity([
            'activityId' => $action->get('id'),
            'type' => $type,
            'object' => $action->all(),
        ]);
        $actionModel->object_type = $action->get('object.type', '');
        $actionModel->target_id = $target->id;
        // Try to find the actor to store it
        try {
            $actor = Actor::where('activityId', $actorActivityId)->firstOrFail();
            $actionModel->actor_id = $actor->id;
        } catch (ModelNotFoundException) {
            Log::debug('Unknown actor', ['id' => $actorActivityId]);
        }
        $actionModel->save();

        switch($type) {
            case 'Follow':
                ProcessFollowAction::dispatch(new Follow($action->all()));
                break;

            case 'Like':
                ProcessLikeAction::dispatch(new Like($action->all()));
                break;

            case 'Undo':
                ProcessUndoAction::dispatch(new Undo($action->all()));
                // $this->handleUndoActivity();
                break;

                // case 'View':
                // $this->handleViewActivity();
                // break;

                // case 'Reject':
                // $this->handleRejectActivity();
                // break;

                // case 'Delete':
                // $this->handleDeleteActivity();
                // break;

                // case 'Add':
                // 	break;

                // case 'Create':
                // 	break;

                // case 'Announce':
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
                // break;
        }

        return response()->activityJson();

        // Follow, replies... come in here
    }
}
