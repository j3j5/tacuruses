<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use ActivityPhp\Type;
use App\Http\Controllers\Controller;
use App\Http\Middleware\OnlyContentType;
use App\Jobs\ActivityPub\ProcessAnnounceAction;
use App\Jobs\ActivityPub\ProcessCreateAction;
use App\Jobs\ActivityPub\ProcessFollowAction;
use App\Jobs\ActivityPub\ProcessLikeAction;
use App\Jobs\ActivityPub\ProcessUndoAction;
use App\Models\ActivityPub\Activity;
use App\Models\ActivityPub\ActivityAnnounce;
use App\Models\ActivityPub\ActivityFollow;
use App\Models\ActivityPub\ActivityLike;
use App\Models\ActivityPub\ActivityUndo;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\ParameterBag;

class InboxController extends Controller
{
    public function __construct()
    {
        $this->middleware(OnlyContentType::class . ':application/activity+json');
    }

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
        /** @type \Symfony\Component\HttpFoundation\ParameterBag $action */
        $action = $request->json();

        // Remove the actor from session
        $actor = $request->actorModel;
        $action->remove('actorModel');

        $type = $action->get('type');

        Log::debug('Processing ' . $type . ' action from single inbox');
        $activityModel = null;
        if ($type !== 'Create') {
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
        }

        // Make sure it hasn't been already processed
        if ($activityModel instanceof Activity && $activityModel->accepted) {
            Log::info('Model already processed and accepted, ignoring');
            return response()->activityJson();
        }

        $activityStream = Type::create($type, $action->all());
        // Go ahead, process it
        switch ($type) {
            case 'Follow':
                if (!$activityModel instanceof ActivityFollow) {
                    throw new RuntimeException('Unknown activity model');
                }
                /** @var \ActivityPhp\Type\Extended\Activity\Follow $activityStream */
                ProcessFollowAction::dispatchAfterResponse($activityStream, $activityModel);
                break;

            case 'Like':
                if (!$activityModel instanceof ActivityLike) {
                    throw new RuntimeException('Unknown activity model');
                }/** @var \ActivityPhp\Type\Extended\Activity\Like $activityStream */
                ProcessLikeAction::dispatchAfterResponse($activityStream, $activityModel);
                break;
            case 'Announce':    // Share/Boost
                if (!$activityModel instanceof ActivityAnnounce) {
                    throw new RuntimeException('Unknown activity model');
                }
                /** @var \ActivityPhp\Type\Extended\Activity\Announce $activityStream */
                ProcessAnnounceAction::dispatchAfterResponse($activityStream, $activityModel);
                break;
            case 'Undo':
                if (!$activityModel instanceof ActivityUndo) {
                    throw new RuntimeException('Unknown activity model');
                }
                /** @var \ActivityPhp\Type\Extended\Activity\Undo $activityStream */
                ProcessUndoAction::dispatchAfterResponse($activityStream, $activityModel);
                break;
            case 'Create':
                /** @var \App\Domain\ActivityPub\Mastodon\Create $activityStream */
                ProcessCreateAction::dispatchAfterResponse($actor, $activityStream);
                break;
            case 'Accept':
            case 'Reject':
            case 'Add':
            case 'Remove':
            case 'Block':
            case 'Flag':
            case 'Update':
            case 'Move':
            case 'Delete':
            default:
                Log::warning('Unknown/unsupported verb on inbox', ['class' => __CLASS__, 'payload' => $action, 'activityStream' => $activityStream]);
                abort(422, 'Unknow type of action');
        }

        return response()->activityJson();
    }

    private function tryToFindTarget(ParameterBag $action) : LocalActor|LocalNote
    {
        return match ($action->get('type')) {
            'Follow' => $this->tryToFindActorTarget($action->get('object')),
            'Announce' => $this->tryToFindNoteTarget($action->get('object')),
            'Like' => $this->tryToFindNoteTarget($action->get('object')),
            'Undo' => $this->tryToFindUndoTarget($action->get('object')),
            default => throw new RuntimeException("Type '" . $action->get('type') . "' is not implemented yet!"),
        };
    }

    private function tryToFindUndoTarget(array $object) : LocalActor|LocalNote
    {
        return match ($object['type']) {
            'Follow' => $this->tryToFindActorTarget($object['object']),
            'Announce' => $this->tryToFindNoteTarget($object['object']),
            'Like' => $this->tryToFindNoteTarget($object['object']),
            default => throw new RuntimeException("Undo Type '" . $object['type'] . "' is not implemented yet!"),
        };
    }

    private function tryToFindActorTarget(string $activityId) : LocalActor
    {
        return LocalActor::byActivityId($activityId)->firstOrFail();
    }

    private function tryToFindNoteTarget(string $activityId) : LocalNote
    {
        return LocalNote::byActivityId($activityId)->firstOrFail();
    }
}
