<?php

namespace Database\Factories\ActivityPub;

use ActivityPhp\Type\AbstractObject;
use App\Enums\ActivityTypes;
use App\Models\ActivityPub\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityPub\Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Activity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activityId' => fake()->url(),
            'accepted' => false,
        ];
    }

    public function type(ActivityTypes $type)
    {
        return $this->state(
            fn (array $attrs)  => ['type' => $type->value]
        );
    }

    public function object(AbstractObject $object)
    {
        return $this->state(
            fn(array $attrs) => ['object' => $object->toArray()]
        );
    }

    public function accepted()
    {
        return $this->state(fn(array $attrs) => ['accepted' => true]);
    }
}
