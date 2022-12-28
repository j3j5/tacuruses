<!DOCTYPE HTML>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <link rel="alternate" type="application/activity+json" href="{{ route('user.show', [$user]) }}" title="ActivityPub profile">
        <link rel="webmention" href="https://{{ request()->getHost() }}/webmentions">
    </head>
    <body>
        <data class="u-photo" value="{{ $user->header }}"></data>
        <div class="container">

            <h1>Hi, I&#39;m {{ $user->name }}<span class="fancy">.</span></h1>
            <img src="{{ $user->avatar }}">
            <span class="handle">{{ '@' . $user->username }}</span>

            <h2>

            </h2>
        </div>
    </body>
</html>