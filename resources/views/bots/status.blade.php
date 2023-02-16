<!DOCTYPE HTML>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <link rel="alternate" type="application/activity+json" href="https://{{ request()->getHost() }}/{{ $note->getActor()->username }}" title="ActivityPub profile">
        {{-- <link rel="webmention" href="https://{{ request()->getHost() }}/webmentions"> --}}
    </head>
    <body>
        <data class="u-photo" value="{{ $note->getActor()->avatar }}"></data>
        <div class="container">
            <div class="header"><img height="200" width="100%" src="{{ $note->getActor()->header }}" ></div>
            <h1>{{ get_class($note) }}<span class="fancy">.</span></h1>
            <img src="{{ $note->getActor()->avatar }}" height="100" width="100">

            <span class="name">{{ $note->getActor()->name }}</span>
            <span class="handle">{{'@' . $note->getActor()->username }}</span>

            <h2>
                {{ (string) $note->getText() }}
            </h2>
        </div>
    </body>
</html>