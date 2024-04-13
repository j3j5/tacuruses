<?php

namespace Database\Factories\ActivityPub;

use App\Models\ActivityPub\Share;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityPub\Share>
 */
class ShareFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Share::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'created_at' => now(),
            'updated_at' => now(),
            // 'actor_id' => '',
            // 'target_id' => '',
            'activityId' => fake()->url(),
        ];
    }
}
