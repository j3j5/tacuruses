<?php

namespace App\Livewire\Pulse;

use App\Enums\Pulse\RecordTypes;
use App\Recorders\Deliveries;
use Illuminate\Support\Facades\Config;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

#[Lazy]
class TopDeliveries extends Card
{
    public function render()
    {
        return view('livewire.pulse.top-deliveries', [
            'config' => Config::get('pulse.recorders.' . Deliveries::class),
            'topServers' => $this->aggregate(RecordTypes::DELIVERY_INSTANCE->value, ['count']),
        ]);
    }
}
