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
        $username = fake()->userName();
        return [
            'created_at' => now(),
            'updated_at' => now(),
            'name' => fake()->name(),
            'username' => $username,
            'avatar' => mb_substr(fake()->filePath() . '.jpg', 1),
            'header' => mb_substr(fake()->filePath() . '.jpg', 1),
            'bio' => fake()->text(),
            'language' => fake()->languageCode(),
            'activityId' => route('actor.show', ['actor' => $username]),
            'url' => route('actor.show', ['actor' => $username]),
            'type' => 'Person', // or Service
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
