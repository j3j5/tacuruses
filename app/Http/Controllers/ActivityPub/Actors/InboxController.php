<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use ActivityPhp\Type;
use App\Enums\ActivityTypes;
use App\Exceptions\AppException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\OnlyContentType;
use App\Jobs\ActivityPub\ProcessAcceptAction;
use App\Jobs\ActivityPub\ProcessAnnounceAction;
use App\Jobs\ActivityPub\ProcessCreateAction;
use App\Jobs\ActivityPub\ProcessFollowAction;
use App\Jobs\ActivityPub\ProcessLikeAction;
use App\Jobs\ActivityPub\ProcessUndoAction;
use App\Models\ActivityPub\Activity;
use App\Models\ActivityPub\ActivityAccept;
use App\Models\ActivityPub\ActivityAnnounce;
use App\Models\ActivityPub\ActivityFollow;
use App\Models\ActivityPub\ActivityLike;
use App\Models\ActivityPub\ActivityUndo;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\InputBag;

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
        /** @type \Symfony\Component\HttpFoundation\InputBag $action */
        $action = $request->json();

        // Remove the actor from session
        $actor = $request->actorModel;
        $action->remove('actorModel');

        $type = $request->input('type');

        Log::debug('Processing ' . $type . ' action from single inbox');
        $activityModel = null;
        if ($type !== 'Create') {
            $target = $this->tryToFindTarget($action);
            $objectType = data_get($request->input('object'), 'type');
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
        switch (ActivityTypes::tryFrom($type)) {
            case ActivityTypes::FOLLOW:
                if (!$activityModel instanceof ActivityFollow) {
                    throw new RuntimeException('Unknown activity model');
                }
                /** @var \ActivityPhp\Type\Extended\Activity\Follow $activityStream */
                ProcessFollowAction::dispatch($activityStream, $activityModel);
                break;

            case ActivityTypes::LIKE:
                if (!$activityModel instanceof ActivityLike) {
                    throw new RuntimeException('Unknown activity model');
                }/** @var \ActivityPhp\Type\Extended\Activity\Like $activityStream */
                ProcessLikeAction::dispatch($activityStream, $activityModel);
                break;
            case ActivityTypes::ANNOUNCE:    // Share/Boost
                if (!$activityModel instanceof ActivityAnnounce) {
                    throw new RuntimeException('Unknown activity model');
                }
                /** @var \ActivityPhp\Type\Extended\Activity\Announce $activityStream */
                ProcessAnnounceAction::dispatch($activityStream, $activityModel);
                break;
            case ActivityTypes::UNDO:
                if (!$activityModel instanceof ActivityUndo) {
                    throw new RuntimeException('Unknown activity model');
                }
                /** @var \ActivityPhp\Type\Extended\Activity\Undo $activityStream */
                ProcessUndoAction::dispatch($activityStream, $activityModel);
                break;
            case ActivityTypes::CREATE:
                /** @var \App\Domain\ActivityPub\Mastodon\Create $activityStream */
                ProcessCreateAction::dispatch($actor, $activityStream);
                break;
            case ActivityTypes::ACCEPT:
                if (!$activityModel instanceof ActivityAccept) {
                    Log::warning("Unknown/unsupported ACCEPT activity on inbox ($type)", [
                        'model' => $activityModel,
                    ]);
                    throw new RuntimeException('Unknown activity model');
                }
                ProcessAcceptAction::dispatch($activityModel);
                break;
            case ActivityTypes::UPDATE:
            case ActivityTypes::DELETE:
            case ActivityTypes::REJECT:
            case ActivityTypes::ADD:
            case ActivityTypes::REMOVE:
            case ActivityTypes::BLOCK:
            case ActivityTypes::FLAG:
            case ActivityTypes::MOVE:
            default:
                Log::warning("Unknown/unsupported verb on inbox ($type)", [
                    'payload' => $action,
                    'activityStream' => $activityStream,
                ]);
                abort(422, "Unknown type of action ($type)");
        }

        return response()->activityJson([], Response::HTTP_ACCEPTED);
    }

    private function tryToFindTarget(InputBag $action) : LocalActor|LocalNote
    {
        return match (ActivityTypes::tryFrom($action->get('type'))) {
            ActivityTypes::FOLLOW => $this->tryToFindActorTarget((string) $action->get('object')),
            ActivityTypes::ANNOUNCE => $this->tryToFindNoteTarget((string) $action->get('object')),
            ActivityTypes::LIKE => $this->tryToFindNoteTarget((string) $action->get('object')),
            ActivityTypes::UNDO => $this->tryToFindUndoTarget(Arr::wrap($action->all('object'))),
            ActivityTypes::ACCEPT => $this->tryToFindAcceptTarget(Arr::wrap($action->all('object'))),
            default => throw new AppException("Type '" . (string) $action->get('type') . "' is not implemented yet!"),
        };
    }

    private function tryToFindAcceptTarget(array $object) : LocalActor|LocalNote
    {
        return match (ActivityTypes::tryFrom($object['type'])) {
            ActivityTypes::FOLLOW => $this->tryToFindActorTarget($object['actor']),
            ActivityTypes::ANNOUNCE => $this->tryToFindNoteTarget($object['actor']),
            ActivityTypes::LIKE => $this->tryToFindNoteTarget($object['object']),
            default => throw new AppException("Accept Type '" . $object['type'] . "' is not implemented yet!"),
        };
    }

    private function tryToFindUndoTarget(array $object) : LocalActor|LocalNote
    {
        return match (ActivityTypes::tryFrom($object['type'])) {
            ActivityTypes::FOLLOW => $this->tryToFindActorTarget($object['object']),
            ActivityTypes::ANNOUNCE => $this->tryToFindNoteTarget($object['object']),
            ActivityTypes::LIKE => $this->tryToFindNoteTarget($object['object']),
            default => throw new AppException("Undo Type '" . $object['type'] . "' is not implemented yet!"),
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
