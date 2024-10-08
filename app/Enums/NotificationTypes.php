<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationTypes:string
{
    case LIKE = 'like';
    case REPLY = 'reply';
    case MENTION = 'mention';
    case SHARE = 'share';
    case FOLLOW = 'follow';
    case UNFOLLOW = 'unfollow';
}
