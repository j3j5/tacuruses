@extends('layouts.master')

@push('head-meta')
    <title>{{ config('app.name') }} | Home</title>
@endpush


@section('content')
<div class="grid grid-flow-row auto-rows-fr grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 w-full justify-center m-6">
    @foreach($actors as $actor)
    <div class="p-8 max-w-xl border-solid border-b-2 border-gray-300">
        <a class="no-underline" href="{{ route('actor.show', [$actor]) }}">
            <div class="w-full">
                <div class="h-32 w-full p-2 mb-2">
                    <img class="h-full m-auto" src="{{ $actor->header }}" />
                </div>
                <div class="mb-4 pb-4">
                    <x-avatar
                        :actor="$actor"
                        class="w-16 mr-2 relative"
                    />
                    <div class="font-bold text-xl pb-1">{{ $actor->name }}</div>
                    <div class="text-base text-slate-500">
                        <span>{{'@' . $actor->username }}</span><span class="opacity-0">@</span><span class="p-1 bg-slate-200 rounded">{{ $actor->domain }}</span>
                    </div>
                    {{-- <span class="inline-block pl-2 pb-2 ml-2">{{ '@' . $actor->username }}</span> --}}
                </div>
                <div class="p-2 m-2">
                    <h1>{!! $actor->bio !!}</h1>
                </div>

                <div class="flex">
                    <div class="text-sm pt-3 pb-3 leading-relaxed">
                        <strong class="">{{ $actor->followers_count ?? 0 }}</strong>
                        <span>Followers</span>
                        <strong class="ml-3">{{ $note->following_count ?? 0 }}</strong>
                        <span class="pr-3">Following</span>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>
@endsection