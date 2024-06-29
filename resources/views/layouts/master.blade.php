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
    <body class="font-sans text-base antialiased bg-gray-100">
        <div class="min-h-screen">
            @yield('content')
        </div>

        <footer class="border-solid border-t-2 border-gray-500 p-8 mt-4">
            @yield('footer')
            <div class="copyright">
                <ul class="list-none list-inside text-center text-lg text-slate-600">
                    <li class="inline-block p-3 ml-1 mr-1">Made with&nbsp;<span class="inline-flex relative top-0.5">@icon('heart', 'fill-slate-600 w-4 h-4 hover:animate-pulse ease-in-out duration-300')</span>&nbsp;in Uruguay&nbsp;<span class="inline-flex relative top-0.5">@icon('mate', 'fill-slate-600 w-5 h-5 hover:scale-125 ease-in-out duration-300')</span>
                    </li>&bull;<li class="inline-block p-3 ml-1 mr-1">
                        <span class="inline-block rotate-180 hover:text-xl ease-in-out duration-300">&copy;</span> Julio Foulquie 2023 @if(now()->year > 2023)- {{now()->year}} @endif
                    </li>&bull;<li class="inline-block p-3 ml-1 mr-1"><span class="inline-flex relative top-1.5">@icon('rocket-launch', 'fill-slate-600 w-5 h-5 -rotate-45 hover:-translate-y-[200rem] ease-in-out duration-1000')</span> To the Fediverse and beyond! <span class="inline-flex relative top-1">@icon('ant', 'fill-slate-600 w-5 h-5 hover:rotate-90 hover:rotate-90 ease-in-out duration-1000')</span>
                    </li>
                </ul>
                @php
                $iconClasses = 'inline-block fill-slate-500 hover:fill-slate-900 w-6 h-6 hover:transition-all ease-in-out duration-300 hover:scale-150';
                $icons = ['keyboard', 'headphones', 'coffee', 'pets', 'plant']; shuffle($icons);
                @endphp
                <ul class="list-none list-inside text-center">
                    @foreach($icons as $icon)<li class="inline-block p-2">@icon($icon, $iconClasses)</li>@endforeach
                </ul>
            </div>
        </footer>

        @stack('footer-scripts')
    </body>
</html>