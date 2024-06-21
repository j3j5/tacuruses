<x-pulse::card :cols="$cols" :rows="$rows" :class="$class" wire:poll.15s="" wire:poll.visible>
    <x-pulse::card-header name="Top Actors (interactions received)"
        title="Top Actors by interactions"
    >
        <x-slot:icon>
            <x-pulse::icons.sparkles />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand">
        @if ($topActors->isEmpty())
        <x-pulse::no-results />
        @else
        <x-pulse::table>
                <colgroup>
                    <col width="0%" />
                    <col width="100%" />
                    <col width="0%" />
                    <col width="0%" />
                </colgroup>
                <x-pulse::thead>
                    <tr>
                        <x-pulse::th>Actor</x-pulse::th>
                        <x-pulse::th class="text-right">Count</x-pulse::th>
                    </tr>
                </x-pulse::thead>
                <tbody>
                    @foreach ($topActors->take(10) as $actor)
                        @php
                        $user = $users->firstWhere('id', $actor->key);
                        $sampleRate = $config['sample_rate'];
                        @endphp
                        <tr wire:key="{{ $actor->key }}-spacer" class="h-2 first:h-0"></tr>
                        <tr wire:key="{{ $actor->key }}-row">
                            <x-pulse::td>
                                <a href="{{ route('actor.show', [$user]) }}" target="_blank">
                                <x-pulse::user-card
                                    :user="$user"
                                    extra="{{ $user->canonical_username }}"
                                >
                                </x-pulse::user-card>
                                </a>
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-xl text-gray-900 dark:text-gray-100 font-bold tabular-nums">
                                @if ($sampleRate < 1)
                                    <span title="Sample rate: {{ $sampleRate }}, Raw value: {{ number_format($actor->count) }}">~{{ number_format($actor->count * (1 / $sampleRate)) }}</span>
                                @else
                                    {{ number_format($actor->count) }}
                                @endif
                            </x-pulse::td>
                        </tr>
                    @endforeach
                </tbody>
            </x-pulse::table>

            @if ($topActors->count() > 10)
                <div class="mt-2 text-xs text-gray-400 text-center">Limited to 10 entries</div>
            @endif

        @endif
    </x-pulse::scroll>
</x-pulse::card>