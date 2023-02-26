<?php

declare(strict_types=1);

namespace App\Domain\ActivityPub;

use App\Services\ActivityPub\Context;
use Illuminate\Validation\Rule;

class Follow extends Activity
{
    public const TYPE = 'Follow';

    public readonly string $id;
    public readonly string $type;
    public readonly string $actor;
    public readonly string $target;

    protected array $rules;

    public function __construct(array $activityObject)
    {
        $this->rules = [
            '@context' => ['required', 'string', Rule::in([Context::ACTIVITY_STREAMS])],
            'id' => ['required', 'string'],
            'type' => ['required', 'string'],
            'actor' => ['required', 'string'],
            'object' => ['required', 'string'],
        ];

        $validator = $this->validate($activityObject);
        // Retrieve the validated input...
        $validated = $validator->validated();

        $this->id = $validated['id'];
        $this->type = self::TYPE;
        $this->actor = $validated['actor'];
        $this->target = $validated['object'];
    }
}
