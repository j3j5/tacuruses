@extends('layouts.master')

@section('content')
<div class="relative p-8">
    <a href="{{ route('user.show', [$note->actor]) }}">
        <div class="mb-4 overflow-hidden no-underline">
            <x-avatar
                :actor="$note->actor"
                class="w-16 mr-2 relative border-solid border-4 border-gray-500"
            />
            <div class="font-bold text-xl">{{ $note->actor->name }}</div>
            <div class="text-lg text-slate-400 ">{{ $note->actor->full_username }}</div>
        </div>
    </a>
    <div class="pl-16">
        {{ $note->text }}
    </div>
    <time class="post-time">
        {{ $note->created_at->locale($note->language)->isoFormat('h:mm a - Do MMM YYYY') }}
    </time>
    <div class="post-stats">
        <div class="reactions">
            <strong>{{ $note->shares_count ?? 0 }}</strong>
            <span>Shares</span>
            <strong>{{ $note->likes_count ?? 0 }}</strong>
            <span>Likes</span>
        </div>
        <div class="border-solid border-l-2 border-gray-300 p-3 pr-0">
            @foreach ($note->peers()->take(10)->get() as $peer)
            <a class="" href="{{ $peer->url }}" target="_blank" rel="noopener noreferrer">
                <x-avatar :actor="$peer" class="w-6 mr-2" />
            </a>
            @endforeach
        </div>
    </div>
    <div class="post-actions">
        <span>💬</span>
        <strong>{{ $note->replies_count ?? 0 }}</strong>

        <span>🔀</span>
        <strong>{{ $note->shares_count }}</strong>

        <span>⭐</span>
        <strong>{{ $note->likes_count }}</strong>

    </div>
</div>
@endsection