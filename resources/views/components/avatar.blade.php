<img
    {{ $attributes->merge(['class' => 'block float-left rounded-full border-solid border-2 border-gray-500']) }}
    {{-- class="{{ $attributes->merge(['class' => 'block float-left rounded-full']) }}" --}}
    src="{{ $actor->avatar }}"
    @empty($actor->name)
    title="{{ $actor->canonical_username }}"
    @else
    title="{{ $actor->name }} ({{ $actor->canonical_username }})"
    @endif
    alt="Avatar of {{ $actor->canonical_username }}"
    onerror="this.onerror=null; this.src='{{ $fallback }}'"
>