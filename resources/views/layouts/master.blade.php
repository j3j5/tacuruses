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
    <body class="font-sans text-base antialiased bg-gray-100 dark:bg-gray-900">
        <div class="min-h-screen">
            @yield('content')
        </div>

        <footer class="border-solid border-t-2 border-gray-500 p-8 mt-4">
            @yield('footer')
            <div class="copyright">
                <ul class="list-none list-inside text-center text-lg text-slate-600">
                    <li class="inline-block p-4">Made with&nbsp;<span class="inline-flex">@icon('heart', 'inline-block fill-slate-600 w-4 h-4')</span>&nbsp;in Uruguay&nbsp;<span class="inline-flex">@icon('mate', 'inline-block fill-slate-600 w-5 h-5')</span>
                    </li>&bull;<li class="inline-block p-4">
                        <span class="inline-block rotate-180">&copy;</span> Julio Foulquie 2023 @if(now()->year > 2023)- {{now()->year}} @endif
                    </li>&bull;<li class="inline-block p-4">To the Fediverse and beyond! @icon('rocket-launch', 'inline-block fill-slate-600 w-5 h-5')
                    </li>
                </ul>
                @php
                $iconClasses = 'inline-block fill-slate-500 hover:fill-slate-900 w-6 h-6';
                $icons = ['heart', 'keyboard', 'headphones', 'coffee', 'pets', 'plant']; shuffle($icons);
                @endphp
                <ul class="list-none list-inside text-center">
                    @foreach($icons as $icon)<li class="inline-block p-2">@icon($icon, $iconClasses)</li>@endforeach
                </ul>
            </div>
        </footer>

        @stack('footer-scripts')
    </body>
</html>