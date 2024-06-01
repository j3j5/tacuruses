@extends('layouts.master')

@push('head-meta')
    <title>{{ config('app.name') }} | {{ $actor->name }} ({{ $actor->canonical_username }})</title>

    {{-- ActivityPub --}}
    {{-- <link rel="webmention" href="https://{{ request()->getHost() }}/webmentions"> --}}
    <link rel="alternate" type="application/activity+json" href="{{ route('actor.show', [$actor]) }}" title="ActivityPub profile">
    <link rel="alternate" type="application/rss+xml" title="{{ $actor->name }} Feed" href="{{ route('feed.actor.rss', [$actor]) }}">

    {{-- OpenGraph --}}
    <meta name="author" content="{{ $actor->canonical_username }}">
    <meta property="og:site_name" content="{{ config('app.name') }}"  />
    <meta property="og:title" content="{{ $actor->name }} ({{ $actor->canonical_username }}) profile"/>

    <meta property="og:type" content="profile"/>
    <meta property="og:profile:first_name" content="{{ $actor->name }}" />
    {{-- <meta property="og:profile:last_name" content="" /> --}}
    <meta property="og:profile:username" content="{{ $actor->canonical_username }}" />

    <meta property="description" name="description" content="{{ $actor->bio }}" />
    <meta property="og:description" content="{{ $actor->bio }}"  />
    <meta property="og:url" content="{{ route('actor.show', [$actor]) }}"  />
    <meta property="og:locale" content="{{ $actor->language }}">

    <meta property="og:image" content="{{ $actor->avatar }}" />
    {{-- <meta content="1280" property="og:image:width" /> --}}
    {{-- <meta content="640" property="og:image:height" /> --}}


    {{-- <meta property="og:updated_time" content="" /> --}}
    {{-- <meta property="article:published_time" content="2009-02-27T10:43:58+00:00" /> --}}
    {{-- <meta property="article:modified_time" content="2009-02-18T15:16:13+00:00" /> --}}
@endpush


@section('content')
<div class="w-full flex justify-center m-6">
    <div class="flex-auto relative p-8 max-w-3xl">
        <div class="w-full">
            <div class="w-full p-2 mb-2"><img class="w-full" src="{{ $actor->header }}" /></div>
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

            <div class="flex border-solid border-b-2 border-gray-300">
                <div class="text-sm pt-3 pb-3 leading-relaxed">
                    <strong class="">{{ $actor->followers_count ?? 0 }}</strong>
                    <span>Followers</span>
                    <strong class="ml-3">{{ $note->following_count ?? 0 }}</strong>
                    <span class="pr-3">Following</span>
                </div>
            </div>

            <div class="mt-2 pt-2 pb-2 w-full">
            @foreach ($notes->loadMissing('peers') as $note)
                @include('actors._note', ['peers' => $note->peers, 'avatar_size' => 'w-10'])
            @endforeach

            {{ $notes->links() }}
            </div>
        </div>
    </div>
</div>
@endsection