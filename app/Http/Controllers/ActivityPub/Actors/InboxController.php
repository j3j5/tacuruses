<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Actors;

use App\Domain\ActivityPub\Follow;
use App\Domain\ActivityPub\Like;
use App\Http\Controllers\Controller;
use App\Jobs\ActivityPub\ProcessFollowAction;
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
        // Log::warning("verb: $type", ['class' => __CLASS__, 'payload' => $action]);
        switch($type) {
            // case 'Add':
            // 	break;

            // case 'Create':
            // 	break;

            case 'Follow':
                ProcessFollowAction::dispatch(new Follow($action->all()));
                break;

                // case 'Announce':
                // 	break;

                // case 'Accept':
                // 	break;

            case 'Delete':
                // $this->handleDeleteActivity();
                break;

            case 'Like':
                // ProcessLikeAction::dispatch(new Like($action->all()));
                // if(LikeValidator::validate($this->payload) == false) { return; }
                // $this->handleLikeActivity();
                break;

            case 'Reject':
                // $this->handleRejectActivity();
                break;

            case 'Undo':
                // $this->handleUndoActivity();
                break;

            case 'View':
                // $this->handleViewActivity();
                break;

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
