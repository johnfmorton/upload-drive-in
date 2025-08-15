<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Setup' }} - {{ config('app.name', 'Upload Drive-In') }}</title>

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
            /* Basic setup styles when Vite is not available */
            .min-h-dvh { min-height: 100vh; }
            .bg-gradient-to-b { background: linear-gradient(to bottom, var(--tw-gradient-stops)); }
            .from-gray-100 { --tw-gradient-from: #f3f4f6; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(243, 244, 246, 0)); }
            .to-gray-300 { --tw-gradient-to: #d1d5db; }
            .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
            .shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
            .animate-spin { animation: spin 1s linear infinite; }
            @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        </style>
    @endif
</head>

<body class="font-sans text-gray-900 antialiased">
    <div class="min-h-dvh bg-gradient-to-b from-gray-100 to-gray-300">
        <!-- Setup Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <x-application-logo class="h-8 w-8" />
                        <h1 class="text-xl font-semibold text-gray-900">
                            {{ config('app.name', 'Upload Drive-In') }} Setup
                        </h1>
                    </div>
                    @if(isset($currentStep) && isset($totalSteps))
                        <div class="text-sm text-gray-500">
                            Step {{ $currentStep }} of {{ $totalSteps }}
                        </div>
                    @endif
                </div>
            </div>
        </header>

        <!-- Progress Indicator -->
        @if(isset($currentStep) && isset($totalSteps) && isset($steps))
            <div class="bg-white border-b border-gray-200">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    <nav aria-label="Setup progress" role="progressbar" aria-valuenow="{{ $currentStep }}" aria-valuemin="1" aria-valuemax="{{ $totalSteps }}">
                        <ol class="flex items-center justify-between">
                            @foreach($steps as $index => $step)
                                @php
                                    $stepNumber = $index + 1;
                                    $isCompleted = $stepNumber < $currentStep;
                                    $isCurrent = $stepNumber == $currentStep;
                                    $isUpcoming = $stepNumber > $currentStep;
                                @endphp
                                <li class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                                    <div class="flex items-center">
                                        <div class="flex items-center justify-center w-8 h-8 rounded-full border-2
                                            {{ $isCompleted ? 'bg-green-600 border-green-600 text-white' : '' }}
                                            {{ $isCurrent ? 'border-blue-600 text-blue-600 bg-white' : '' }}
                                            {{ $isUpcoming ? 'border-gray-300 text-gray-400 bg-white' : '' }}">
                                            @if($isCompleted)
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            @else
                                                <span class="text-sm font-medium">{{ $stepNumber }}</span>
                                            @endif
                                        </div>
                                        <span class="ml-2 text-sm font-medium
                                            {{ $isCompleted ? 'text-green-600' : '' }}
                                            {{ $isCurrent ? 'text-blue-600' : '' }}
                                            {{ $isUpcoming ? 'text-gray-400' : '' }}">
                                            {{ $step }}
                                        </span>
                                    </div>
                                    @if(!$loop->last)
                                        <div class="flex-1 mx-4 h-0.5
                                            {{ $isCompleted ? 'bg-green-600' : 'bg-gray-300' }}">
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                </div>
            </div>
        @endif

        <!-- Main Content -->
        <main class="py-8">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    {{ $slot }}
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="mt-auto py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center text-sm text-gray-500">
                    <p>{{ config('app.name', 'Upload Drive-In') }} Initial Setup</p>
                </div>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>

</html>
