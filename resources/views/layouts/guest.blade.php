<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Upload Drive-In') }}</title>

        {{-- Inject Brand Color CSS Variable --}}
        <style>
            :root {
                --brand-color: {{ config('branding.color', '#6366F1') }};
            }
        </style>

        @include('components.favicon')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

       <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-dvh flex flex-col justify-between">
            <div class="flex flex-col justify-center items-center pb-6 sm:pt-0 bg-gradient-to-b from-gray-100 to-gray-300 px-6 flex-1">
              @yield('content')
            </div>
            @include('components.footer')
        </div>
        @stack('scripts')
    </body>
</html>
