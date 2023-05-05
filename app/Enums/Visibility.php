<?php

namespace App\Enums;

enum Visibility:string
{
    case PUBLIC = 'public';
    case UNLISTED = 'unlisted';
    case PRIVATE = 'private';
    case DIRECT = 'direct';
}
