<div class="mb-4 overflow-hidden no-underline">
    <a href="{{ route('user.show', [$note->actor]) }}">
        <x-avatar
            :actor="$note->actor"
            class="w-16 mr-2 relative border-solid border-4 border-gray-500"
        />
    </a>
    <a class="block float-right text-slate-500 hover:text-slate-700" href="{{ route('note.show', [$note->actor, $note]) }}">
        @icon('clock', 'inline-block w-4 h-4')
        <span class="">
            {{-- {{ $note->created_at->locale($note->language)->isoFormat('h:mm a - Do MMM YYYY') }} --}}
            {{ $note->published_at->locale($note->language)->diffForHumans() }}
        </span>
    </a>
    <a href="{{ route('user.show', [$note->actor]) }}">
        <div class="font-bold text-xl pb-1">{{ $note->actor->name }}</div>
        <div class="text-base text-slate-500">
            {{ $note->actor->full_username }}
        </div>
    </a>
</div>
<div class="text-slate-900 text-lg leading-relaxed mt-2 mb-2">
    {{ $note->content }}
</div>
<div class="flex border-solid border-b-2 border-gray-300">
    <div class="text-sm pt-3 pb-3 leading-relaxed">
        <strong class="">{{ $note->shares_count ?? 0 }}</strong>
        <span>Shares</span>
        <strong class="ml-3">{{ $note->likes_count ?? 0 }}</strong>
        <span class="pr-3">Likes</span>
    </div>
    <div class="border-solid border-l-2 border-gray-300 p-3 pr-0">
        @foreach ($peers as $peer)
        <a class="" href="{{ $peer->url }}" target="_blank" rel="noopener noreferrer">
            <x-avatar :actor="$peer" class="w-6 mr-2" />
        </a>
        @endforeach
    </div>
</div>
<div class="text-slate-500 text-sm font-bold mt-4">
    @php $iconClasses = 'inline-block fill-slate-500 hover:fill-slate-700 w-6 h-6'; @endphp
    <span class="hover:text-slate-700 ">
        <span class="mr-2">
            @icon('reply', $iconClasses)
        </span>
        <strong class="mr-4">{{ $note->replies_count ?? 0 }}</strong>
    </span>

    <span class="hover:text-slate-700 ">
        <span class="mr-2">
            @icon('boost', $iconClasses)
        </span>
        <strong class="mr-4">{{ $note->shares_count ?? 0 }}</strong>
    </span>

    <span class="hover:text-slate-700 ">
        <span class="mr-2">
            @icon('star', $iconClasses)
        </span>
        <strong class="mr-4">{{ $note->likes_count ?? 0 }}</strong>
    </span>
    <a href="#" class="hover:text-slate-700">
        <span class="mr-2">
            @icon('share', $iconClasses)
        </span>
    </a>

</div>