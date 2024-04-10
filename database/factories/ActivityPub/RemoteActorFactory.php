<?php

namespace Database\Factories\ActivityPub;

use App\Models\ActivityPub\RemoteActor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityPub\RemoteActor>
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
        $activityId = fake()->url();
        $username = fake()->userName();

        return [
            'created_at' => now()->subMonth(),
            'updated_at' => now()->subMonth(),
            'name' => fake()->name(),
            'username' => $username,
            'avatar' => fake()->url(),
            'header' => fake()->url(),
            'bio' => fake()->text(),
            'language' => fake()->languageCode(),
            'activityId' => $activityId,
            'following_url' => $activityId . 'following',
            'followers_url' => $activityId . 'followers',
            'url' => $activityId,
            'inbox' => $activityId . 'inbox',
            'sharedInbox' => fake()->url(),
            'publicKeyId' => $activityId . '#main-key',
            'type' => 'Person',
            'actor_type' => 'remote',
        ];

    }

    public function withPublicKey(string $publicKey)
    {
        return $this->state(fn (array $attributes) => ['publicKey' => $publicKey]);
    }
}
