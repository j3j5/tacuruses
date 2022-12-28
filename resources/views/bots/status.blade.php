<!DOCTYPE HTML>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <link rel="alternate" type="application/activity+json" href="https://{{ request()->getHost() }}/cadaUruguayo" title="ActivityPub profile">
        <link rel="webmention" href="https://{{ request()->getHost() }}/webmentions">
    </head>
    <body>
        <data class="u-photo" value="https://{{ request()->getHost() }}/pic.png"></data>
        <div class="container">

            <h1>{{ get_class($status) }}<span class="fancy">.</span></h1>
            <img src="https://{{ request()->getHost() }}/pic.png">

            <span class="handle">@j3j5</span>

            <h2>
                {{ (string) $status->getTweet() }}
            </h2>
        </div>
    </body>
</html>