<x-pulse>
    <livewire:pulse.servers cols="full" />

    {{-- <livewire:pulse.usage cols="4" rows="2" /> --}}
    <livewire:pulse.top-deliveries cols="4" rows="2" />

    <livewire:pulse.queues cols="4" />

    <livewire:pulse.cache cols="4" />

    <livewire:pulse.slow-queries cols="8" />

    {{-- <livewire:pulse.interactions-actors cols="6" />

    <livewire:pulse.interactions-notes cols="6" /> --}}

    <livewire:pulse.exceptions cols="6" />

    <livewire:pulse.slow-requests cols="6" />

    <livewire:pulse.slow-jobs cols="6" />

    <livewire:pulse.slow-outgoing-requests cols="6" />

</x-pulse>
