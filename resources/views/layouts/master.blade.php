<!DOCTYPE HTML>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @isset($actor)
        {{-- <link rel="webmention" href="https://{{ request()->getHost() }}/webmentions"> --}}
        <link rel="alternate" type="application/activity+json" href="{{ route('actor.show', [$actor]) }}" title="ActivityPub profile">
        <link rel="alternate" type="application/atom+xml" title="{{ $actor->name }} Feed" href="{{ route('actor.feed.atom', [$actor]) }}">
        <link rel="alternate" type="application/rss+xml" title="{{ $actor->name }} Feed" href="{{ route('actor.feed.rss', [$actor]) }}">
        @endisset

        <link rel="stylesheet" href="{{ mix('css/app.css') }}">
        <script src="{{ mix('js/app.js') }}" defer></script>
    </head>
    <body class="font-sans text-base antialiased">
        @yield('content')
    </body>
</html>