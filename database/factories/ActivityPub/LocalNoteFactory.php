<?php

namespace Database\Factories\ActivityPub;

use App\Enums\Visibility;
use App\Models\ActivityPub\LocalNote;
use App\Services\ActivityPub\Context;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityPub\LocalNote>
 */
class LocalNoteFactory extends NoteFactory
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
        return array_merge(parent::definition(), [
            'note_type' => 'local',
        ]);
    }

    public function public(): Factory
    {
        return parent::public()->afterMaking(function (LocalNote $note) {
            $note->fillRecipients();
        })->afterCreating(function (LocalNote $note) {
            $note->fillRecipients();
        });
    }

    public function unlisted(): Factory
    {
        return parent::unlisted()->afterMaking(function (LocalNote $note) {
            $note->fillRecipients();
        })->afterCreating(function (LocalNote $note) {
            $note->fillRecipients();
        });
    }

    public function private(): Factory
    {
        return parent::private()->afterMaking(function (LocalNote $note) {
            $note->fillRecipients();
        })->afterCreating(function (LocalNote $note) {
            $note->fillRecipients();
        });
    }

}
