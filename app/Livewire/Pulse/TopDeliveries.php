<?php

declare(strict_types=1);

namespace App\Livewire\Pulse;

use App\Enums\Pulse\RecordTypes;
use App\Recorders\Deliveries;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

#[Lazy]
class TopDeliveries extends Card
{
    public function render() : View
    {
        return view('livewire.pulse.top-deliveries', [
            'config' => Config::get('pulse.recorders.' . Deliveries::class),
            'topServers' => $this->aggregate(RecordTypes::DELIVERY_INSTANCE->value, ['count']),
        ]);
    }
}
