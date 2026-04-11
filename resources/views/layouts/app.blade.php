<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Upload Drive-In') }}</title>

        {{-- Inject Brand Color CSS Variable --}}
        <style @cspNonce>
            :root {
                --brand-color: {{ config('branding.color', '#6366F1') }};
            }
        </style>

        @include('components.favicon')
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.bunny.net/css?family=instrument-serif:400,400i&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-warm-900">
        <div class="min-h-dvh bg-cream-50 flex flex-col justify-between noise-overlay relative overflow-hidden">
            {{-- Subtle decorative background --}}
            <div class="absolute top-0 right-0 w-[600px] h-[600px] opacity-[0.03] pointer-events-none" style="background: radial-gradient(circle at 70% 30%, var(--color-accent-500) 0%, transparent 70%);"></div>
            <div class="relative z-10">
              @include('layouts.navigation')
              <!-- Page Heading -->
              @isset($header)
                  <header class="bg-white/70 backdrop-blur-sm border-b border-cream-200">
                      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                          {{ $header }}
                      </div>
                  </header>
              @endisset
              <!-- Page Content -->
              <main id="main-content" style="animation: pageReveal 0.4s ease-out both;">
                  {{ $slot }}
              </main>
            </div>
            <div class="relative z-10">
                @include('components.footer')
            </div>
        </div>
        @stack('modals')
        <script @cspNonce>
            document.getElementById('main-content')?.addEventListener('animationend', function() {
                this.style.animation = '';
            }, { once: true });
        </script>
        @stack('scripts')
    </body>
</html>
