<?php

declare(strict_types=1);

namespace App\Domain\ActivityPub;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as FacadesValidator;

abstract class Action
{
    protected Validator $validator;
    protected array $rules = [];

    /**
     *
     * @param array $activityObject
     * @throws \Illuminate\Validation\ValidationException
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validate(array $activityObject) : Validator
    {
        $validator = FacadesValidator::make($activityObject, $this->rules);

        $validator->validate();

        return $validator;
    }
}
