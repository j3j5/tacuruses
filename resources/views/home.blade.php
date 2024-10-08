@extends('layouts.master')

@push('head-meta')
    <title>{{ config('app.name') }} | Home</title>
@endpush


@section('content')
<div class="w-full p-8 text-center m-4">
    <div class="p-8">
        <h1 class="text-4xl font-bold">Bots.uy</h1>
        <h3><a href="https://gitlab.com/j3j5/tacuruses" target="_blank" rel="noopener noreferrer">Powered by Tacuruses</a></h3>
    </div>
    @php
        $headerAlt = "Still from a Futurama episode where Bender is kicked out " .
                    "of the moon's theme park. He's standing in front of a closed door. " .
                    "The door has the text 'Twitter' on it. He's saying " .
                    "\"I'm gonna go create my own social media server. " .
                    "With ActivityPub support and bots\".";
    @endphp
    <img class="m-auto rounded-lg" src="{{ asset('img/bender.jpg') }}" alt="{{ $headerAlt }}">
</div>

<div class="grid grid-flow-row auto-rows-fr grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 justify-center m-6">
    @foreach($actors as $actor)
    <article class="p-8 max-w-xl shadow-md ">
        <div class="w-full">
            <a class="no-underline" href="{{ route('actor.show', [$actor]) }}" >
                <div class="h-32 w-full p-2 mb-2">
                    <img class="h-full m-auto" src="{{ $actor->header }}" />
                </div>
                <div class="mb-4 pb-4">
                    <x-avatar
                        :actor="$actor"
                        class="w-16 mr-2 relative"
                    />
                    <div class="font-bold text-xl pb-1">{{ $actor->name }}</div>
                    </div>
                    <div class="text-base text-black"><span>{{'@' . $actor->username }}</span><span class="opacity-0">@</span><span class="p-1 bg-slate-200 rounded">{{ $actor->domain }}</span>
                    {{-- <span class="inline-block pl-2 pb-2 ml-2">{{ '@' . $actor->username }}</span> --}}
                </div>
            </a>

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
    </article>
    @endforeach
</div>
{{-- <div class="m-8 p-8"> --}}
    {{-- {{ $actors->links() }} --}}
{{-- </div> --}}

@endsection
