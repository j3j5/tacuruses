@extends('layouts.master')

@section('content')
<div class="w-full flex justify-center m-6">
    <div class="flex-auto relative p-8 max-w-md">
        @include('bots._note')
    </div>
</div>
@endsection