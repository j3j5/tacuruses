<!DOCTYPE html>
<html
    @if(isset($note))
    lang="{{ $note->actor->language }}"
    @else
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @endif
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        @stack('head-meta')

        <link rel="stylesheet" href="{{ mix('css/app.css') }}">
        @stack('head-styles')

        <script src="{{ mix('js/app.js') }}" defer></script>
        @stack('head-scripts')
    </head>
    <body class="">
        @yield('content')
        @stack('footer-scripts')
    </body>
</html>