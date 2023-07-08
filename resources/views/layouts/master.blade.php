<!DOCTYPE HTML>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @stack('head-meta')

        <link rel="stylesheet" href="{{ mix('css/app.css') }}">
        @stack('head-styles')

        <script src="{{ mix('js/app.js') }}" defer></script>
        @stack('head-scripts')
    </head>
    <body class="font-sans text-base antialiased">
        @yield('content')

        @stack('footer-scripts')
    </body>
</html>