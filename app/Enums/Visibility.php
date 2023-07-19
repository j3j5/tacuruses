<?php

namespace App\Enums;

enum Visibility:string
{
    /** Visible to everybody on all feeds */
    case PUBLIC = 'public';
    /** Visible to everybody but not pushed to Local or Federated feed*/
    case UNLISTED = 'unlisted';
    /** Followers only */
    case PRIVATE = 'private';
    /** Mentioned users only */
    case DIRECT = 'direct';
}
