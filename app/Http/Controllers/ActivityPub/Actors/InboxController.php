<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use ActivityPhp\Type;
use ActivityPhp\Type\Extended\Activity\Announce;
use ActivityPhp\Type\Extended\Activity\Follow;
use ActivityPhp\Type\Extended\Activity\Like;
use ActivityPhp\Type\Extended\Activity\Undo;
use App\Domain\ActivityPub\Mastodon\Create;
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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\InputBag;
use Webmozart\Assert\Assert;

class InboxController extends Controller
{
    public function __construct()
    {
        $this->middleware(OnlyContentType::class . ':application/activity+json');
    }

    /**
     *
     * @param \Illuminate\Http\Request $request
     * @throws \Webmozart\Assert\InvalidArgumentException
     * @throws \Symfony\Component\HttpFoundation\Exception\BadRequestException
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \App\Exceptions\AppException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Exception
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request) : JsonResponse
    {
        $type = ActivityTypes::tryFrom($request->string('type')->toString());
        Assert::isInstanceOf($type, ActivityTypes::class);

        /** @type \Symfony\Component\HttpFoundation\InputBag $action */
        $action = $request->json();

        // Remove the actor from session
        $actor = $request->actorModel;
        $action->remove('actorModel');

        Log::debug('Processing ' . $type->value . ' action from single inbox');
        $activityModel = null;
        if ($type !== ActivityTypes::CREATE) {
            $target = $this->tryToFindTarget($action);
            $objectType = data_get($request->input('object'), 'type');
            // store the action
            $activityModel = Activity::updateOrCreate([
                'activityId' => $action->get('id'),
                'type' => $type->value,
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
            return response()->activityJson([], Response::HTTP_ACCEPTED);
        }

        $activityStream = Type::create($type->value, $action->all());
        // Go ahead, process it
        switch ($type) {
            case ActivityTypes::FOLLOW:
                Assert::isInstanceOf($activityStream, Follow::class);
                Assert::isInstanceOf($activityModel, ActivityFollow::class);
                ProcessFollowAction::dispatch($activityStream, $activityModel);
                break;

            case ActivityTypes::LIKE:
                Assert::isInstanceOf($activityStream, Like::class);
                Assert::isInstanceOf($activityModel, ActivityLike::class);
                ProcessLikeAction::dispatch($activityStream, $activityModel);
                break;
            case ActivityTypes::ANNOUNCE:    // Share/Boost
                Assert::isInstanceOf($activityStream, Announce::class);
                Assert::isInstanceOf($activityModel, ActivityAnnounce::class);
                ProcessAnnounceAction::dispatch($activityStream, $activityModel);
                break;
            case ActivityTypes::UNDO:
                Assert::isInstanceOf($activityStream, Undo::class);
                Assert::isInstanceOf($activityModel, ActivityUndo::class);
                ProcessUndoAction::dispatch($activityStream, $activityModel);
                break;
            case ActivityTypes::CREATE:
                Assert::isInstanceOf($activityStream, Create::class);
                ProcessCreateAction::dispatch($actor, $activityStream);
                break;
            case ActivityTypes::ACCEPT:
                Assert::isInstanceOf($activityModel, ActivityAccept::class);
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
                Log::warning("Unknown/unsupported verb on inbox ({$type->value})", [
                    'payload' => $action,
                    'activityStream' => $activityStream,
                ]);
                abort(422, "Unknown type of action ({$type->value})");
        }

        return response()->activityJson([], Response::HTTP_ACCEPTED);
    }

    private function tryToFindTarget(InputBag $action) : LocalActor|LocalNote
    {
        return match (ActivityTypes::tryFrom((string) $action->get('type'))) {
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
