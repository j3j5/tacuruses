<img
    {{ $attributes->merge(['class' => 'block float-left rounded-full']) }}
    {{-- class="{{ $attributes->merge(['class' => 'block float-left rounded-full']) }}" --}}
    src="{{ $actor->avatar }}"
    title="{{ $actor->name }} ({{ $actor->full_username }})"
    alt="Avatar of {{ $actor->full_username }}"
    onerror="this.onerror=null;this.src='{{ $fallback }}'"
>