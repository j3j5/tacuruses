
<x-pulse::card :cols="$cols" :rows="$rows" :class="$class" wire:poll.5s="">
    <x-pulse::card-header name="Top Servers (delivery)"
        title="Top instances by activities delivered"
    >
        <x-slot:icon>
            <x-pulse::icons.cloud-arrow-up />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand">
        @if ($topServers->isEmpty())
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
                        <x-pulse::th>Instance</x-pulse::th>
                        <x-pulse::th class="text-right">Count</x-pulse::th>
                    </tr>
                </x-pulse::thead>
                <tbody>
                    @foreach ($topServers->take(100) as $server)
                        <tr wire:key="{{ $server->key }}-spacer" class="h-2 first:h-0"></tr>
                        <tr wire:key="{{ $server->key }}-row">
                            <x-pulse::td class="overflow-hidden">
                                <code class="block text-xs text-gray-900 dark:text-gray-100 truncate" title="{{ $server->key }}">
                                    {{ $server->key }}
                                </code>
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 font-bold">
                                @if ($config['sample_rate'] < 1)
                                    <span title="Sample rate: {{ $config['sample_rate'] }}, Raw value: {{ number_format($server->count) }}">~{{ number_format($server->count * (1 / $config['sample_rate'])) }}</span>
                                @else
                                    {{ number_format($server->count) }}
                                @endif
                            </x-pulse::td>
                        </tr>
                    @endforeach
                </tbody>
            </x-pulse::table>

            @if ($topServers->count() > 100)
                <div class="mt-2 text-xs text-gray-400 text-center">Limited to 100 entries</div>
            @endif

        @endif
    </x-pulse::scroll>
</x-pulse::card>