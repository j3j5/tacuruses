<?php

namespace Database\Factories\ActivityPub;

use App\Models\ActivityPub\RemoteNote;
use App\Services\ActivityPub\Context;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityPub\RemoteNote>
 */
class RemoteNoteFactory extends NoteFactory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RemoteNote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return array_merge(parent::definition(), [
            'note_type' => 'remote',
        ]);
    }

    public function public(): Factory
    {
        return parent::public()->afterMaking(function (RemoteNote $note) {
            $note->to = [Context::ACTIVITY_STREAMS_PUBLIC];
            $note->cc = [$note->actor->followers_url];
        })->afterCreating(function (RemoteNote $note) {
            $note->to = [Context::ACTIVITY_STREAMS_PUBLIC];
            $note->cc = [$note->actor->followers_url];
        });
    }

    public function unlisted(): Factory
    {
        return parent::unlisted()->afterMaking(function (RemoteNote $note) {
            $note->to = [$note->actor->followers_url];
            $note->cc = [Context::ACTIVITY_STREAMS_PUBLIC];
        })->afterCreating(function (RemoteNote $note) {
            $note->to = [$note->actor->followers_url];
            $note->cc = [Context::ACTIVITY_STREAMS_PUBLIC];
        });
    }

    public function private(): Factory
    {
        return parent::private()->afterMaking(function (RemoteNote $note) {
            $note->to = [$note->actor->followers_url];
            $note->cc = [];
        })->afterCreating(function (RemoteNote $note) {
            $note->to = [$note->actor->followers_url];
            $note->cc = [];
        });
    }
}
