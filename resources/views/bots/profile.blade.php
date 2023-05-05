@extends('layouts.master')

@section('content')
<div class="w-full flex justify-center m-6">
    <div class="flex-auto relative p-8 max-w-md">
        <div class="w-full">
            <div class="w-full p-2 mb-2"><img class="w-full" src="{{ $actor->header }}" /></div>
            <div class="mb-4 pb-4">
                <x-avatar
                    :actor="$actor"
                    class="w-16 mr-2 relative"
                />
                <div class="font-bold text-xl pb-1">{{ $actor->name }}</div>
                <div class="text-base text-slate-500">
                    {{ $actor->full_username }}
                </div>
                {{-- <span class="inline-block pl-2 pb-2 ml-2">{{ '@' . $actor->username }}</span> --}}
            </div>
            <div class="p-2 m-2">
                <h1>{{ $actor->bio }}</h1>
            </div>

            <div class="flex border-solid border-b-2 border-gray-300">
                <div class="text-sm pt-3 pb-3 leading-relaxed">
                    <strong class="">{{ $actor->followers_count ?? 0 }}</strong>
                    <span>Followers</span>
                    <strong class="ml-3">{{ $note->following_count ?? 0 }}</strong>
                    <span class="pr-3">Following</span>
                </div>
            </div>

            <div class="mt-2 pt-2 pb-2 w-full">
            @foreach ($actor->notes->loadMissing('peers') as $note)
                @include('bots._note', ['peers' => $note->peers, 'avatar_size' => 'w-10'])
            @endforeach
            </div>
        </div>
    </div>
</div>
@endsection