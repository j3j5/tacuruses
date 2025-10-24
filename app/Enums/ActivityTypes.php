<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * @url https://www.w3.org/TR/activitystreams-vocabulary/#activity-types
 */
enum ActivityTypes:string
{
    case ACCEPT = 'Accept';
    case ADD = 'Add';
    case ANNOUNCE = 'Announce';
    case ARRIVE = 'Arrive';
    case BLOCK = 'Block';
    case CREATE = 'Create';
    case DELETE = 'Delete';
    case DISLIKE = 'Dislike';
    case FLAG = 'Flag';
    case FOLLOW = 'Follow';
    case IGNORE = 'Ignore';
    case INVITE = 'Invite';
    case JOIN = 'Join';
    case LEAVE = 'Leave';
    case LIKE = 'Like';
    case LISTEN = 'Listen';
    case MOVE = 'Move';
    case OFFER = 'Offer';
    case QUESTION = 'Question';
    case QUOTE_REQUEST = 'QuoteRequest';
    case REJECT = 'Reject';
    case READ = 'Read';
    case REMOVE = 'Remove';
    case TENTATIVE_REJECT = 'TentativeReject';
    case TENTATIVE_ACCEPT = 'TentativeAccept';
    case TRAVEL = 'Travel';
    case UNDO = 'Undo';
    case UPDATE = 'Update';
    case VIEW = 'View';
}
