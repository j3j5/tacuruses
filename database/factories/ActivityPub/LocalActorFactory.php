<?php

namespace Database\Factories\ActivityPub;

use App\Models\ActivityPub\LocalActor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityPub\LocalActor>
 */
class LocalActorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LocalActor::class;

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
            'name' => fake()->name(),
            'username' => fake()->userName(),
            'avatar' => mb_substr(fake()->filePath() . '.jpg', 1),
            'header' => mb_substr(fake()->filePath() . '.jpg', 1),
            'bio' => fake()->text(),
            'language' => fake()->languageCode(),
            'activityId' => fake()->url(),
            'url' => fake()->url(),
            'type' => 'Service',
            'actor_type' => 'local',
        ];
    }

        /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (LocalActor $actor) {
            if (!Storage::disk('local')->exists("keys/local/{$actor->id}/private.pem")) {
                $actor->generateKeys();
            }
        });

    }
}
