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
    @php
        $assetService = app(\App\Services\AssetValidationService::class);
        $assetsAvailable = $assetService->areAssetRequirementsMet();
    @endphp
    
    @if($assetsAvailable)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <!-- Fallback CSS for when Vite assets are not available -->
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            /* Basic styles when Vite is not available */
            .min-h-dvh { min-height: 100vh; }
            .bg-gradient-to-b { background: linear-gradient(to bottom, var(--tw-gradient-stops)); }
            .from-gray-100 { --tw-gradient-from: #f3f4f6; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(243, 244, 246, 0)); }
            .to-gray-300 { --tw-gradient-to: #d1d5db; }
            .shadow-md { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        </style>
    @endif

</head>

<body class="font-sans text-gray-900 antialiased">
    <div class="min-h-dvh flex flex-col justify-between">
        <div
            class="flex flex-col justify-center items-center pb-6 sm:pt-0 bg-gradient-to-b from-gray-100 to-gray-300 px-6 flex-1">
            <div class="w-full max-w-md px-6 py-6 bg-white shadow-md overflow-hidden rounded-lg">
                {{ $slot }}
            </div>
        </div>
        @include('components.footer')
    </div>
    @stack('scripts')
</body>

</html>
