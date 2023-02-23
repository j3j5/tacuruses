<?php

declare(strict_types=1);

namespace App\Domain\ActivityPub;

class Like extends Action
{
    public const TYPE = 'Like';

    public readonly string $id;
    public readonly string $type;
    public readonly string $actor;
    public readonly string $target;

    protected array $rules = [
        '@context' => ['required', 'string', 'in:https://www.w3.org/ns/activitystreams'],
        'id' => ['required', 'string'],
        'type' => ['required', 'string'],
        'actor' => ['required', 'string'],
        'object' => ['required', 'string'],
    ];

    public function __construct(array $activityObject)
    {
        $validator = $this->validate($activityObject);

        // Retrieve the validated input...
        $validated = $validator->validated();

        $this->id = $validated['id'];
        $this->type = self::TYPE;
        $this->actor = $validated['actor'];
        $this->target = $validated['object'];
    }
}
