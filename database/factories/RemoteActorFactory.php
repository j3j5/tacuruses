<?php

namespace Database\Factories;

use App\Models\ActivityPub\RemoteActor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use phpseclib3\Crypt\RSA;

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
            'name' => fake()->name(),
            'created_at' => now(),
            'updated_at' => now(),
            'username' => fake()->userName(),
            'avatar' => fake()->url(),
            'header' => fake()->url(),
            'bio' => fake()->text(),
            // 'alsoKnownAs' => '',
            // 'properties' => json(),
            'language' => fake()->languageCode(),
            'activityId' => fake()->url(),
            'url' => fake()->url(),
            'type' => 'Person',
            'inbox' => fake()->url(),
            'sharedInbox' => fake()->url(),
            'publicKeyId' => fake()->url(),
            'actor_type' => 'remote',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return static
     */
    public function withPublicKey(string $publicKey)
    {
        return $this->state(fn (array $attributes) => [
            'publicKey' => $publicKey,
        ]);
    }
}
