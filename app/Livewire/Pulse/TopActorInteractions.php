<?php

declare(strict_types=1);

namespace App\Livewire\Pulse;

use App\Enums\Pulse\RecordTypes;
use App\Models\ActivityPub\LocalActor;
use App\Recorders\Interactions;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

#[Lazy]
class TopActorInteractions extends Card
{
    public function render() : View
    {
        $aggregates = $this->aggregate(RecordTypes::ACTOR_INTERACTIONS->value, ['count']);
        $users = LocalActor::whereIn('id', $aggregates->take(10)->pluck('key'))->get();

        return view('livewire.pulse.top-actor-interactions', [
            'config' => Config::get('pulse.recorders.' . Interactions::class),
            'topActors' => $aggregates,
            'users' => $users,
        ]);
    }
}
