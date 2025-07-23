<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Upload Drive-in') }}</title>

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
    <body class="font-sans antialiased">
        <div class="min-h-dvh bg-gradient-to-b from-gray-100 to-gray-300 flex flex-col justify-between">
            <div>
              @include('layouts.navigation')
              <!-- Page Heading -->
              @isset($header)
                  <header class="bg-gradient-to-b from-gray-50 via-white to-white shadow">
                      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                          {{ $header }}
                      </div>
                  </header>
              @endisset
              <!-- Page Content -->
              <main>
                  {{ $slot }}
              </main>
            </div>
            @include('components.footer')
        </div>
        @stack('scripts')
    </body>
</html>
