<?php

declare(strict_types=1);

namespace App\Livewire\Pulse;

use App\Enums\Pulse\RecordTypes;
use App\Models\ActivityPub\LocalNote;
use App\Recorders\Interactions;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

#[Lazy]
class TopNoteInteractions extends Card
{
    public function render() : View
    {
        $aggregates = $this->aggregate(RecordTypes::NOTE_INTERACTIONS->value, ['count']);
        $notes = LocalNote::with('actor')->whereIn('id', $aggregates->take(20)->pluck('key'))->get();

        return view('livewire.pulse.top-note-interactions', [
            'config' => Config::get('pulse.recorders.' . Interactions::class),
            'topNotes' => $aggregates,
            'notes' => $notes,
        ]);
    }
}
