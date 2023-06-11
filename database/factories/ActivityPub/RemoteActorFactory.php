<?php

namespace Database\Factories\ActivityPub;

use App\Models\ActivityPub\RemoteActor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityPub\LocalActor>
 */
class RemoteActorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RemoteActor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {

        return [
            'created_at' => now()->subMonth(),
            'updated_at' => now()->subMonth(),
            'name' => fake()->name(),
            'username' => fake()->userName(),
            'avatar' => fake()->url,
            'header' => fake()->url,
            'bio' => fake()->text(),
            'language' => fake()->languageCode(),
            'activityId' => fake()->url(),
            'url' => fake()->url(),
            'inbox' => fake()->url(),
            'sharedInbox' => fake()->url(),
            'publicKeyId' => fake()->url() . '#main-key',
            'type' => 'Person',
            'actor_type' => 'local',
        ];

    }

    public function withPublicKey(string $publicKey)
    {
        return $this->state(fn (array $attributes) => ['publicKey' => $publicKey]);
    }
}
