@extends('layouts.embed')

@push('head-meta')
    <title>{{ $note->actor->name }} ({{ $note->actor->canonical_username }}): "{{ strip_tags($note->content) }}"</title>

    {{-- ActivityPub --}}
    <link rel="alternate" type="application/activity+json" href="{{ route('actor.show', [$note->actor]) }}" title="ActivityPub profile">
    <link rel="alternate" type="application/rss+xml" title="{{ $note->actor->name }} Feed" href="{{ route('feed.actor.rss', [$note->actor]) }}">
    <script>
    // CODE extracted from parts of Mastodon Project licensed with AGPLv3
    // See https://github.com/mastodon/mastodon/blob/4743657ba24e83c376e9f477fbf49114e6f09a57/app/javascript/mastodon/ready.js
    //     https://github.com/mastodon/mastodon/blob/4743657ba24e83c376e9f477fbf49114e6f09a57/app/javascript/entrypoints/public.tsx#L57

/**
 * Ready function to make sure iframe is loaded to set the proper
 * height based on content.
 *
 * @param {(() => void) | (() => Promise<void>)} callback
 * @returns {Promise<void>}
 */
function ready(callback) {
    return new Promise((resolve, reject) => {
        function loaded() {
            let result;
            try {
                result = callback();
            } catch (err) {
                reject(err);
                return;
            }

            if (typeof result?.then === 'function') {
                result.then(resolve).catch(reject);
            } else {
                resolve();
            }
        }

        if (['interactive', 'complete'].includes(document.readyState)) {
            loaded();
        } else {
            document.addEventListener('DOMContentLoaded', loaded);
        }
    });
}

// Set the height when the message from the embedde page loads
window.addEventListener('message', e => {
    const data = e.data || {};

    if (!window.parent || data.type !== 'setHeight') {
        return;
    }

    ready(() => {
        window.parent.postMessage({
        type: 'setHeight',
        id: data.id,
        height: document.getElementsByTagName('html')[0].scrollHeight,
        }, '*');
    });
});
</script>
@endpush

@section('content')
<div class="">
    @include('actors._note', ['avatar_size' => 'w-16', 'embed' => true])
</div>
@endsection