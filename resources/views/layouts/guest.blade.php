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
    @php
        $assetService = app(\App\Services\AssetValidationService::class);
        $assetsAvailable = $assetService->areAssetRequirementsMet();
    @endphp
    
    @if($assetsAvailable)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <!-- Fallback CSS for when Vite assets are not available -->
        <script src="https://cdn.tailwindcss.com" @cspNonce></script>
        <style @cspNonce>
            /* Basic styles when Vite is not available */
            .min-h-dvh { min-height: 100vh; }
            .bg-gradient-to-b { background: linear-gradient(to bottom, var(--tw-gradient-stops)); }
            .from-gray-100 { --tw-gradient-from: #f3f4f6; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(243, 244, 246, 0)); }
            .to-gray-300 { --tw-gradient-to: #d1d5db; }
            .shadow-md { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        </style>
    @endif

</head>

<body class="font-sans text-warm-900 antialiased">
    <div class="min-h-dvh flex flex-col justify-between bg-cream-50 noise-overlay relative overflow-hidden">
        {{-- Decorative geometric accent --}}
        <div class="absolute top-0 right-0 w-[600px] h-[600px] opacity-[0.04] pointer-events-none" style="background: radial-gradient(circle at 70% 30%, var(--color-accent-500) 0%, transparent 70%);"></div>
        <div class="absolute bottom-0 left-0 w-[400px] h-[400px] opacity-[0.03] pointer-events-none" style="background: radial-gradient(circle at 30% 70%, var(--color-warm-900) 0%, transparent 70%);"></div>

        <div class="relative z-10 flex flex-col justify-center items-center pb-6 sm:pt-0 px-6 flex-1">
            <div class="w-full max-w-lg px-10 py-12 bg-white/80 backdrop-blur-sm border border-cream-200 overflow-hidden rounded-3xl shadow-[0_2px_8px_rgba(28,25,23,0.04),0_12px_40px_rgba(28,25,23,0.06)]" style="animation: cardFloat 0.5s ease-out both;">
                {{ $slot }}
            </div>
        </div>
        <div class="relative z-10">
            @include('components.footer')
        </div>
    </div>
    @stack('scripts')
</body>

</html>
