<?php

declare(strict_types=1);

namespace App\Domain\ActivityPub;

use Illuminate\Contracts\Validation\Validator;

abstract class Action
{
    protected Validator $validator;
    protected array $rules = [];
}
