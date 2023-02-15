<?php

declare(strict_types=1);

namespace App\Domain\ActivityPub;

use Illuminate\Support\Facades\Validator;

class Undo extends Action
{
    public const TYPE = 'Undo';

    public readonly string $id;
    public readonly string $type;
    public readonly string $actor;
    /**
     *
     * @var array{id: string, type: string, actor: string, object:string}
     */
    public readonly array $objectToUndo;

    protected array $rules = [
        '@context' => ['required', 'string', 'in:https://www.w3.org/ns/activitystreams'],
        'id' => ['required', 'string'],
        'type' => ['required', 'string'],
        'actor' => ['required', 'string'],
        'object' => ['required', 'array'],
        'object.id' => ['required', 'string'],
        'object.type' => ['required', 'string'],
        'object.actor' => ['required', 'string'],
        'object.object' => ['required', 'string'],
    ];

    public function __construct(array $activityObject)
    {
        $validator = $this->validate($activityObject);
        // Retrieve the validated input...
        $validated = $validator->validated();

        $this->id = $validated['id'];
        $this->type = self::TYPE;
        $this->actor = $validated['actor'];
        $this->objectToUndo = $validated['object'];
    }
}
