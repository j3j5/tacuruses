<img
    {{ $attributes->merge(['class' => 'block float-left rounded-full border-solid border-2 border-gray-500']) }}
    {{-- class="{{ $attributes->merge(['class' => 'block float-left rounded-full']) }}" --}}
    src="{{ $actor->avatar }}"
    @empty($actor->name)
    title="{{ $actor->full_username }}"
    @else
    title="{{ $actor->name }} ({{ $actor->full_username }})"
    @endif
    alt="Avatar of {{ $actor->full_username }}"
    onerror="this.onerror=null;this.src='{{ $fallback }}'"
>