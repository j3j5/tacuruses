<?php

namespace Database\Factories\ActivityPub;

use App\Enums\Visibility;
use App\Models\ActivityPub\LocalNote;
use App\Services\ActivityPub\Context;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityPub\LocalActor>
 */
class LocalNoteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LocalNote::class;

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
            // 'replyTo_id' => '',
            'activityId' => fake()->url,
            'published_at' => now(),
            'content' => null,
            'contentMap' => [fake()->languageCode() => fake()->sentences(random_int(1, 5), true)],
            // 'summary' => '',
            // 'summaryMap' => '',
            'type' => 'Note',
            'sensitive' => false,
            'to' => Context::ACTIVITY_STREAMS_PUBLIC,
            // 'bto' => '',
            // 'cc' => '',
            // 'bcc' => '',
            // 'inReplyTo' => '',
            // 'generator' => '',
            // 'location' => '',
            // 'startTime' => '',
            // 'endTime' => '',
            'visibility' => Visibility::PUBLIC,
            // 'attachments' => '',
            // 'tags' => '',
            // 'repliesRaw' => '',
            // 'source' => '',
            // 'conversation' => '',
            'note_type' => 'local',
            // 'original_content' => '',
        ];
    }

}
