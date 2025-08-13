@props([
    'currentStep' => 'welcome',
    'progress' => 0,
    'steps' => []
])

@php
$defaultSteps = [
    'welcome' => ['title' => 'Welcome', 'description' => 'System checks', 'icon' => 'home'],
    'database' => ['title' => 'Database', 'description' => 'Configure database', 'icon' => 'database'],
    'admin' => ['title' => 'Admin User', 'description' => 'Create administrator', 'icon' => 'user'],
    'storage' => ['title' => 'Cloud Storage', 'description' => 'Connect Google Drive', 'icon' => 'cloud'],
    'complete' => ['title' => 'Complete', 'description' => 'Setup finished', 'icon' => 'check']
];

$allSteps = !empty($steps) ? $steps : $defaultSteps;
$stepKeys = array_keys($allSteps);
$currentIndex = array_search($currentStep, $stepKeys);
$totalSteps = count($allSteps);
@endphp

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <!-- Progress bar -->
    <div class="mb-6">
        <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
            <span>Setup Progress</span>
            <span>{{ $progress }}% Complete</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-blue-600 h-3 rounded-full transition-all duration-500 ease-out" 
                 style="width: {{ $progress }}%"></div>
        </div>
    </div>

    <!-- Step indicators -->
    <div class="flex items-center justify-between">
        @foreach($allSteps as $stepKey => $stepInfo)
        @php
        $stepIndex = array_search($stepKey, $stepKeys);
        $isCompleted = $stepIndex < $currentIndex;
        $isCurrent = $stepKey === $currentStep;
        $isUpcoming = $stepIndex > $currentIndex;
        @endphp
        
        <div class="flex flex-col items-center flex-1 {{ $loop->last ? '' : 'relative' }}">
            <!-- Step circle -->
            <div class="relative z-10 flex items-center justify-center w-10 h-10 rounded-full border-2 
                        {{ $isCompleted ? 'bg-green-100 border-green-500' : '' }}
                        {{ $isCurrent ? 'bg-blue-100 border-blue-500' : '' }}
                        {{ $isUpcoming ? 'bg-gray-100 border-gray-300' : '' }}">
                
                @if($isCompleted)
                    <!-- Completed step - checkmark -->
                    <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                @elseif($isCurrent)
                    <!-- Current step - icon -->
                    @switch($stepInfo['icon'])
                        @case('home')
                            <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            @break
                        @case('database')
                            <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                            </svg>
                            @break
                        @case('user')
                            <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            @break
                        @case('cloud')
                            <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                            </svg>
                            @break
                        @case('check')
                            <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            @break
                        @default
                            <span class="text-blue-500 font-semibold">{{ $stepIndex + 1 }}</span>
                    @endswitch
                @else
                    <!-- Upcoming step - number -->
                    <span class="text-gray-400 font-semibold">{{ $stepIndex + 1 }}</span>
                @endif
            </div>

            <!-- Step title and description -->
            <div class="mt-2 text-center">
                <div class="text-sm font-medium 
                           {{ $isCompleted ? 'text-green-600' : '' }}
                           {{ $isCurrent ? 'text-blue-600' : '' }}
                           {{ $isUpcoming ? 'text-gray-400' : '' }}">
                    {{ $stepInfo['title'] }}
                </div>
                <div class="text-xs text-gray-500 mt-1 hidden sm:block">
                    {{ $stepInfo['description'] }}
                </div>
            </div>

            <!-- Connecting line -->
            @if(!$loop->last)
            <div class="absolute top-5 left-1/2 w-full h-0.5 
                        {{ $stepIndex < $currentIndex ? 'bg-green-500' : 'bg-gray-300' }}"
                 style="transform: translateX(50%); z-index: 1;"></div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Current step status -->
    @if($currentStep !== 'complete')
    <div class="mt-4 p-3 bg-blue-50 rounded-lg">
        <div class="flex items-center">
            <svg class="h-5 w-5 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <div class="text-sm font-medium text-blue-800">
                    Current Step: {{ $allSteps[$currentStep]['title'] }}
                </div>
                <div class="text-xs text-blue-600">
                    {{ $allSteps[$currentStep]['description'] }}
                </div>
            </div>
        </div>
    </div>
    @endif
</div>