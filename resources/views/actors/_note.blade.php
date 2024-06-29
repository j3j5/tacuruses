<div class="pt-8 pb-8 @unless($fullscreen ?? false) max-w-xl m-auto @endif">
    <header class="mb-4 overflow-hidden no-underline">
        <a href="{{ $note->actor->url }}">
            <x-avatar
                :actor="$note->actor"
                class="{{$avatar_size ?? 'w-10'}} mr-2 relative"
            />
        </a>
        <a class="block float-right text-slate-500 hover:text-slate-700" href="{{ $note->url }}">
            @icon('clock', 'inline-block w-4 h-4')
            <span class="" title="{{ $note->created_at->locale($note->actor->language)->isoFormat('D MMM YYYY - h:mm a') }}">
                {{ $note->published_at->locale($note->actor->language)->diffForHumans() }}
            </span>
        </a>
        <a href="{{ $note->actor->url }}">
            <div class="font-bold pb-1 {{ $name_class ?? 'text-xl' }}">{{ $note->actor->name }}</div>
            <div class="text-base text-slate-500">
                <span>{{'@' . $note->actor->username }}</span><span class="opacity-0">@</span><span class="p-1 bg-slate-200 rounded">{{ $note->actor->domain }}</span>
            </div>
        </a>
    </header>

    <section class="text-slate-900 text-lg leading-relaxed mt-2 mb-2">
        {!! $note->content !!}
        @unless($note->mediaAttachments->isEmpty())
        <div class="flex">
            @foreach ($note->mediaAttachments as $media)
            <div class="p-4 m-auto">
                <a class="" href="{{ $media->remote_url }}" target="_blank" rel="nofollow noopener">
                    <img class="" src="{{ $media->remote_url }}" alt="{{ $media->description }}">
                </a>
            </div>
            @endforeach
        </div>
        @endunless
    </section>

    <section class="flex border-solid border-b-2 border-gray-300">
        <div class="text-sm pt-3 pb-3 leading-relaxed">
            <strong class="">{{ $note->shares_count ?? 0 }}</strong>
            <span>{{ Str::plural('Share', $note->shares_count ?? 0) }}</span>
            <strong class="ml-3">{{ $note->likes_count ?? 0 }}</strong>
            <span class="pr-3">{{ Str::plural('Like', $note->likes_count ?? 0) }}</span>

        </div>
        @isset($peers)
        <div class="border-solid border-l-2 border-gray-300 p-3 pr-0">
            @foreach ($peers as $peer)
            <a class="" href="{{ $peer->url }}" target="_blank" rel="noopener noreferrer">
                <x-avatar :actor="$peer" class="w-6 mr-2" />
            </a>
            @endforeach
        </div>
        @endisset
    </section>
    @unless ($noInteractions ?? false)
    <footer class="flex items-center text-slate-500 text-sm font-bold mt-4">
        @php $iconClasses = 'inline-block fill-slate-500 hover:fill-slate-700 w-6 h-6'; @endphp
        <div class="flex-auto hover:text-slate-700 ">
            <span class="mr-2">
                @icon('reply', $iconClasses)
            </span>
            <strong class="mr-4">{{ $note->replies_count ?? 0 }}</strong>
        </div>

        <div class="flex-auto hover:text-slate-700 ">
            <span class="mr-2">
                @icon('boost', $iconClasses)
            </span>
            <strong class="mr-4">{{ $note->shares_count ?? 0 }}</strong>
        </div>

        <div class="flex-auto hover:text-slate-700 ">
            <span class="mr-2">
                @icon('star', $iconClasses)
            </span>
            <strong class="mr-4">{{ $note->likes_count ?? 0 }}</strong>
        </div>
        <a href="#" class="hover:text-slate-700">
            <div class="flex-auto">
            <span class="mr-2">
                @icon('share', $iconClasses)
            </span>
            </div>
        </a>
    </footer>
    @endunless
</div>