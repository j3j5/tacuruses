<?php

declare(strict_types=1);

namespace App\Domain\ActivityPub;

use App\Services\ActivityPub\Context;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class Announce extends Activity
{
    public const TYPE = 'Announce';

    public readonly string $id;
    public readonly string $type;
    public readonly string $actor;
    public readonly Carbon $published;
    public readonly array $to;
    public readonly array $cc;
    public readonly string $object;

    protected array $rules;

    public function __construct(array $activityObject)
    {
        $this->rules = [
            '@context' => ['required', 'string', Rule::in([Context::ACTIVITY_STREAMS])],
            'id' => ['required', 'string'],
            'type' => ['required', 'string'],
            'actor' => ['required', 'string'],
            'published' => ['required', 'string', 'date_format:Y-m-d\TH:i:s\Z', function ($attribute, $value, $fail) {
                if (Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $value)->isFuture()) {
                    $fail('Time travel alert! The ' . $attribute . ' is in the future.');
                }
            }],
            'to' => ['required', 'array'],
            'cc' => ['required', 'array'],
            'object' => ['required', 'string'],
        ];

        $validator = $this->validate($activityObject);

        // Retrieve the validated input...
        $validated = $validator->validated();

        $this->id = $validated['id'];
        $this->type = self::TYPE;
        $this->actor = $validated['actor'];
        $this->published = Carbon::parse($validated['published']);
        $this->to = $validated['to'];
        $this->cc = $validated['cc'];
        $this->object = $validated['object'];
    }
}
