@extends('layouts.master')

@push('head-meta')
    <title>{{ $note->actor->name }} ({{ $note->actor->canonical_username }}): "{{ strip_tags($note->content) }}"</title>


    {{-- ActivityPub --}}
    {{-- <link rel="webmention" href="https://{{ request()->getHost() }}/webmentions"> --}}
    <link rel="alternate" type="application/activity+json" title="ActivityPub profile for {{ $note->actor->name }}" href="{{ route('actor.show', [$note->actor]) }}">
    <link rel="alternate" type="application/rss+xml" title="{{ $note->actor->name }} Feed" href="{{ route('feed.actor.rss', [$note->actor]) }}">
    <link rel='alternate' type="application/json+oembed" title="{{ route('api.oembed', ['url' => route('note.show', [$note->actor, $note]), 'format' => 'json']) }}" >

    {{-- OpenGraph, see https://ogp.me --}}
    <meta name="author" content="{{ $note->actor->canonical_username }}">
    <meta property="og:site_name" content="{{ config('app.name') }}"  />
    <meta property="og:title" content="{{ $note->actor->name }} ({{ $note->actor->canonical_username }}) said "/>

    <meta property="og:type" content="article"/>
    <meta property="article:published_time" content="{{ $note->created_at->toIso8601String() }}" />
    <meta property="article:modified_time" content="{{ $note->updated_at->toIso8601String() }}" />

    <meta property="description" name="description" content="{{ strip_tags($note->content) }}" />
    <meta property="og:description" content="{{ strip_tags($note->content) }}"  />

    <meta property="og:url" content="{{ route('note.show', [$note->actor, $note]) }}"  />
    <meta property="og:locale" content="{{ $note->actor->language }}">

    @forelse ($note->mediaAttachments as $media)
    <meta property="og:image" content="{{ $media->remote_url }}" />
    <meta property="og:image:type" content="{{ $media->content_type }}" />
    <meta property="og:image:alt" content="{{ $media->description }}" />
    {{-- <meta content="" property="og:image:width" /> --}}
    {{-- <meta content="" property="og:image:height" /> --}}
    @empty
    <meta property="og:image" content="{{ $note->actor->avatar }}" />
    {{-- <meta content="" property="og:image:width" /> --}}
    {{-- <meta content="" property="og:image:height" /> --}}
    @endforelse
@endpush

@section('content')
<div class="w-full flex flex-col justify-center m-4">
        @include('actors._note', ['avatar_size' => 'w-16'])
    <hr>
@foreach ($note->directReplies->load('actor') as $reply)
        @include('actors._note', [
            'name_class' => 'text-l truncate',
            'avatar_size' => 'w-10',
            'note' => $reply,
            'peers' => collect(),
            'noInteractions' => true,
        ])
@endforeach
</div>
@endsection