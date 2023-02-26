<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Domain\ActivityPub\Announce;
use App\Domain\ActivityPub\Follow;
use App\Domain\ActivityPub\Like;
use App\Domain\ActivityPub\Undo;
use App\Http\Controllers\Controller;
use App\Jobs\ActivityPub\ProcessAnnounceAction;
use App\Jobs\ActivityPub\ProcessFollowAction;
use App\Jobs\ActivityPub\ProcessLikeAction;
use App\Jobs\ActivityPub\ProcessUndoAction;
use App\Models\ActivityPub\Activity;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\ParameterBag;

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
        info(__FILE__ . ':' . __LINE__, );
        /** @type \Symfony\Component\HttpFoundation\ParameterBag $action */
        $action = $request->json();

        // Remove the actor from session
        $actor = $request->actorModel;
        $action->remove('actorModel');

        $type = $action->get('type');
        $target = $this->tryToFindTarget($action);
        $objectType = data_get($action->get('object'), 'type');
        // store the action
        $activityModel = Activity::updateOrCreate([
            'activityId' => $action->get('id'),
            'type' => $type,
        ], [
            'object' => $action->all(),
            'object_type' => $objectType,
            'target_id' => $target->id,
            'actor_id' => $actor->id,
        ]);

        switch ($type) {
            case Follow::TYPE:
                ProcessFollowAction::dispatch(new Follow($action->all()), $activityModel);
                break;
            case Like::TYPE:
                ProcessLikeAction::dispatch(new Like($action->all()), $activityModel);
                break;
            case Announce::TYPE:
                ProcessAnnounceAction::dispatch(new Announce($action->all()), $activityModel);
                break;
            case Undo::TYPE:
                ProcessUndoAction::dispatch(new Undo($action->all()), $activityModel);
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
                Log::warning('Unknown verb on inbox', ['class' => __CLASS__, 'payload' => $action]) && abort(422, 'Unknow type of action');
                break;
        }

        return response()->activityJson();

        // Follow, replies... come in here
    }

    private function tryToFindTarget(ParameterBag $action) : LocalActor|Note
    {
        return match ($action->get('type')) {
            Follow::TYPE => $this->tryToFindActorTarget($action->get('object')),
            Announce::TYPE => $this->tryToFindNoteTarget($action->get('object')),
            Like::TYPE => $this->tryToFindNoteTarget($action->get('object')),
            Undo::TYPE => $this->tryToFindUndoTarget($action->get('object')),
            // default => $this->unknownTarget($action),
        };
    }

    private function tryToFindUndoTarget(array $object) : LocalActor|Note
    {
        return match ($object['type']) {
            Follow::TYPE => $this->tryToFindActorTarget($object['object']),
            Announce::TYPE => $this->tryToFindNoteTarget($object['object']),
            Like::TYPE => $this->tryToFindNoteTarget($object['object']),
        };
    }

    private function tryToFindActorTarget(string $activityId) : LocalActor
    {
        return LocalActor::byActivityId($activityId)->firstOrFail();
    }

    private function tryToFindNoteTarget(string $activityId) : Note
    {
        return Note::byActivityId($activityId)->firstOrFail();
    }
}
