<?php

declare(strict_types=1);

namespace App\Enums;

enum InteractionPolicy:string
{
    // manualApproval (https://gotosocial.org/ns#manualApproval): an array of Actor and Collection of Actor objects from whom interactions are subject to manual review
    case MANUAL = 'manualApproval';

    // automaticApproval (https://gotosocial.org/ns#automaticApproval): an array of Actor and Collection of Actor objects from whom interactions are expected to be automatically approved
    case AUTOMATIC = 'automaticApproval';

    public function namespace(): string
    {
        return match($this) {
            self::MANUAL => 'https://gotosocial.org/ns#manualApproval',
            self::AUTOMATIC => 'https://gotosocial.org/ns#automaticApproval',
        };
    }

}
