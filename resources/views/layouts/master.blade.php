<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @stack('head-meta')

        <link rel="stylesheet" href="{{ mix('css/app.css') }}">
        @stack('head-styles')

        <script src="{{ mix('js/app.js') }}" defer></script>
        @stack('head-scripts')
    </head>
    <body class="font-sans text-base antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @yield('content')
        </div>

        @stack('footer-scripts')
    </body>
</html>