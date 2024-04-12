<?php

namespace App\Enums;

/**
 * @url https://www.w3.org/TR/activitystreams-vocabulary/#actor-types
 */
enum ActorTypes:string
{
    case APPLICATION = 'Application';
    case GROUP = 'Group';
    case ORGANIZATION = 'Organization';
    case PERSON = 'Person';
    case SERVICE = 'Service';

}
