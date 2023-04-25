@extends('layouts.master')

@section('content')
<div class="w-full flex flex-col justify-center m-4">
        @include('bots._note', ['avatar_size' => 'w-16'])
    <hr>
@foreach ($note->directReplies->load('actor') as $reply)
        @include('bots._note', [
            'name_class' => 'text-l truncate',
            'avatar_size' => 'w-10',
            'note' => $reply,
        ])
@endforeach
</div>
@endsection