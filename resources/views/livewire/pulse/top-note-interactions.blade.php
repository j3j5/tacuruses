<x-pulse::card :cols="$cols" :rows="$rows" :class="$class" wire:poll.30s="" wire:poll.visible>
    <x-pulse::card-header name="Top Notes (interactions received)"
        title="Top Notes by interactions"
    >
        <x-slot:icon>
            <x-pulse::icons.sparkles />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand">
        @if ($topNotes->isEmpty())
        <x-pulse::no-results />
        @else
        <x-pulse::table>
                <colgroup>
                    <col width="100%" />
                    <col width="0%" />
                </colgroup>
                <x-pulse::thead>
                    <tr>
                        <x-pulse::th>Note</x-pulse::th>
                        <x-pulse::th class="text-right">Count</x-pulse::th>
                    </tr>
                </x-pulse::thead>
                <tbody>
                    @foreach ($topNotes->take(20) as $record)
                        @php
                        $note = $notes->firstWhere('id', $record->key);
                        $user = $note->actor;
                        $sampleRate = $config['sample_rate'];
                        @endphp
                        <tr wire:key="{{ $record->key }}-spacer" class="h-2 first:h-0"></tr>
                        <tr wire:key="{{ $record->key }}-row">
                            <x-pulse::td class="!p-0 truncate max-w-[1px]">
                                <div class="flex flex-col" title="{{ $note->content }}">
                                    <a href="{{ route('note.show', [$user, $note]) }}" target="_blank">
                                    <div class="flex items-center gap-3 overflow-hidden">
                                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}" loading="lazy" class="rounded-full w-8 h-8 object-cover">
                                        <span class="text-s">{{ $user->name }}</span> <span class="text-xs text-gray-900 dark:text-gray-100 truncate">{{ $user->canonical_username }}</span>
                                    </div>
                                    <code class="block text-m text-gray-900 dark:text-gray-100">
                                       {!! $note->content !!}
                                    </code>
                                    </a>
                                </div>
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-xl text-gray-900 dark:text-gray-100 font-bold tabular-nums">
                                @if ($sampleRate < 1)
                                    <span title="Sample rate: {{ $sampleRate }}, Raw value: {{ number_format($record->count) }}">~{{ number_format($record->count * (1 / $sampleRate)) }}</span>
                                @else
                                    {{ number_format($record->count) }}
                                @endif
                            </x-pulse::td>
                        </tr>
                    @endforeach
                </tbody>
            </x-pulse::table>

            @if ($topNotes->count() > 20)
                <div class="mt-2 text-xs text-gray-400 text-center">Limited to 20 entries</div>
            @endif

        @endif
    </x-pulse::scroll>
</x-pulse::card>