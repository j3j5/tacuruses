@extends('layouts.master')

@section('content')
<div class="w-full flex justify-center m-6">
    <div class="flex-auto relative p-8 max-w-md">
        <div class="w-3/5">
            <div class="w-full p-2 mb-2"><img class="w-full" src="{{ $actor->header }}" /></div>
            <div class="p-2 mb-2">
                <img class="inline-block pb-2 w-8 rounded-full" src="{{ $actor->avatar }}">
                <span class="inline-block pb-2 ml-2">{{ '@' . $actor->username }}</span>
            </div>
            <h1>{{ $actor->bio }}</h1>

            @foreach ($actor->notes as $note)
                @include('bots._note')
            @endforeach
        </div>
    </div>
</div>
@endsection