<?php

declare(strict_types=1);

namespace App\Domain\ActivityPub;

use Illuminate\Support\Facades\Validator;

class Like extends Action
{
    public const TYPE = 'Like';

    protected string $actor;
    protected string $object;

    protected array $rules = [
        '@context' => ['required', 'string', 'in:https://www.w3.org/ns/activitystreams'],
        'id' => ['required', 'string'],
        'type' => ['required', 'string'],
        'actor' => ['required', 'string'],
        'object' => ['required', 'string'],
    ];

    public function __construct(array $activityObject)
    {
        $rules = $this->rules;
        $validator = Validator::make($activityObject, $rules);

        $validator->validate();

        // Retrieve the validated input...
        $validated = $validator->validated();

        $this->id - $validated['id'];
        $this->type = self::TYPE;
        $this->actor = $validated['actor'];
        $this->object = $validated['object'];
    }
}
