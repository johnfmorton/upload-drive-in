@props([
    'title' => 'Setup',
    'currentStep' => 1,
    'totalSteps' => 5,
    'steps' => ['Welcome', 'Database', 'Admin User', 'Cloud Storage', 'Complete']
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} - {{ config('app.name', 'Upload Drive-In') }}</title>

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
                    <div class="text-sm text-gray-500">
                        Step {{ $currentStep }} of {{ $totalSteps }}
                    </div>
                </div>
            </div>
        </header>

        <!-- Progress Indicator -->
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