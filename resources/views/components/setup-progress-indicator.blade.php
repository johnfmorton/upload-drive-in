@props([
    'currentStep' => 'welcome',
    'progress' => 0,
    'steps' => [],
    'animated' => true,
    'showEstimatedTime' => true
])

@php
$defaultSteps = [
    'assets' => ['title' => 'Assets', 'description' => 'Build frontend assets', 'icon' => 'build', 'estimatedTime' => '2-3 min'],
    'welcome' => ['title' => 'Welcome', 'description' => 'System checks', 'icon' => 'home', 'estimatedTime' => '1 min'],
    'database' => ['title' => 'Database', 'description' => 'Configure database', 'icon' => 'database', 'estimatedTime' => '2-4 min'],
    'admin' => ['title' => 'Admin User', 'description' => 'Create administrator', 'icon' => 'user', 'estimatedTime' => '1-2 min'],
    'storage' => ['title' => 'Cloud Storage', 'description' => 'Connect Google Drive', 'icon' => 'cloud', 'estimatedTime' => '3-5 min'],
    'complete' => ['title' => 'Complete', 'description' => 'Setup finished', 'icon' => 'check', 'estimatedTime' => '1 min']
];

$allSteps = !empty($steps) ? $steps : $defaultSteps;
$stepKeys = array_keys($allSteps);
$currentIndex = array_search($currentStep, $stepKeys);
$totalSteps = count($allSteps);
@endphp

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6 shadow-sm" 
     x-data="setupProgress({{ $progress }}, '{{ $currentStep }}', {{ $animated ? 'true' : 'false' }})"
     x-init="init()">
    <!-- Progress bar with enhanced animation -->
    <div class="mb-6">
        <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
            <span class="font-medium">Setup Progress</span>
            <div class="flex items-center space-x-2">
                <span x-text="`${animatedProgress}% Complete`" class="font-medium">{{ $progress }}% Complete</span>
                @if($showEstimatedTime && isset($allSteps[$currentStep]['estimatedTime']))
                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                    ~{{ $allSteps[$currentStep]['estimatedTime'] }}
                </span>
                @endif
            </div>
        </div>
        <div class="relative w-full bg-gray-200 rounded-full h-3 overflow-hidden">
            <!-- Animated progress bar -->
            <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-1000 ease-out transform origin-left"
                 :style="`width: ${animatedProgress}%; transform: scaleX(${animatedProgress / 100})`">
                <!-- Shimmer effect -->
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-30 animate-pulse"></div>
            </div>
            <!-- Progress glow effect -->
            <div class="absolute inset-0 bg-blue-400 h-3 rounded-full opacity-20 blur-sm transition-all duration-1000 ease-out"
                 :style="`width: ${animatedProgress}%`"></div>
        </div>
        
        <!-- Progress milestones -->
        <div class="flex justify-between mt-1">
            @foreach([20, 40, 60, 80] as $milestone)
            <div class="w-0.5 h-2 bg-gray-300 rounded-full" 
                 :class="{ 'bg-blue-500': animatedProgress >= {{ $milestone }} }"></div>
            @endforeach
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
            <!-- Step circle with enhanced animations -->
            <div class="relative z-10 flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-500 ease-out transform
                        {{ $isCompleted ? 'bg-green-100 border-green-500 scale-110' : '' }}
                        {{ $isCurrent ? 'bg-blue-100 border-blue-500 scale-110 shadow-lg' : '' }}
                        {{ $isUpcoming ? 'bg-gray-100 border-gray-300 scale-100' : '' }}"
                 :class="{
                     'animate-pulse': currentStep === '{{ $stepKey }}' && animated,
                     'animate-bounce': completedSteps.includes('{{ $stepKey }}')
                 }">
                
                <!-- Glow effect for current step -->
                @if($isCurrent)
                <div class="absolute inset-0 w-10 h-10 bg-blue-400 rounded-full opacity-20 animate-ping"></div>
                @endif
                
                @if($isCompleted)
                    <!-- Completed step - checkmark -->
                    <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                @elseif($isCurrent)
                    <!-- Current step - icon -->
                    @switch($stepInfo['icon'])
                        @case('build')
                            <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 7.172V5L8 4z" />
                            </svg>
                            @break
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

            <!-- Connecting line with animation -->
            @if(!$loop->last)
            <div class="absolute top-5 left-1/2 w-full h-0.5 bg-gray-300 transition-all duration-700 ease-out"
                 style="transform: translateX(50%); z-index: 1;">
                <!-- Animated progress line -->
                <div class="h-full bg-green-500 transition-all duration-1000 ease-out origin-left"
                     :style="`width: ${stepIndex < currentIndex || (stepIndex === currentIndex && animatedProgress > (stepIndex * 20)) ? '100%' : '0%'}`"></div>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Current step status with enhanced feedback -->
    @if($currentStep !== 'complete')
    <div class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="flex-shrink-0 mr-3">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div>
                    <div class="text-sm font-medium text-blue-800">
                        Current Step: {{ $allSteps[$currentStep]['title'] }}
                    </div>
                    <div class="text-xs text-blue-600">
                        {{ $allSteps[$currentStep]['description'] }}
                    </div>
                </div>
            </div>
            
            @if($showEstimatedTime && isset($allSteps[$currentStep]['estimatedTime']))
            <div class="text-right">
                <div class="text-xs text-blue-600 font-medium">Estimated Time</div>
                <div class="text-sm text-blue-800">{{ $allSteps[$currentStep]['estimatedTime'] }}</div>
            </div>
            @endif
        </div>
        
        <!-- Step completion indicator -->
        <div class="mt-3 flex items-center text-xs text-blue-600">
            <div class="flex items-center mr-4">
                <div class="w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></div>
                <span>{{ count(array_filter($allSteps, fn($step, $key) => array_search($key, $stepKeys) < $currentIndex, ARRAY_FILTER_USE_BOTH)) }} completed</span>
            </div>
            <div class="flex items-center mr-4">
                <div class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></div>
                <span>1 in progress</span>
            </div>
            <div class="flex items-center">
                <div class="w-2 h-2 bg-gray-400 rounded-full mr-1"></div>
                <span>{{ count(array_filter($allSteps, fn($step, $key) => array_search($key, $stepKeys) > $currentIndex, ARRAY_FILTER_USE_BOTH)) }} remaining</span>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
function setupProgress(initialProgress, currentStep, animated) {
    return {
        animatedProgress: 0,
        currentStep: currentStep,
        animated: animated,
        completedSteps: [],
        
        init() {
            if (this.animated) {
                // Animate progress bar
                this.animateProgress(initialProgress);
                
                // Mark completed steps
                this.updateCompletedSteps();
            } else {
                this.animatedProgress = initialProgress;
            }
        },
        
        animateProgress(targetProgress) {
            const duration = 1500; // 1.5 seconds
            const startTime = Date.now();
            const startProgress = this.animatedProgress;
            
            const animate = () => {
                const elapsed = Date.now() - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Easing function for smooth animation
                const easeOutCubic = 1 - Math.pow(1 - progress, 3);
                
                this.animatedProgress = Math.round(startProgress + (targetProgress - startProgress) * easeOutCubic);
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            };
            
            requestAnimationFrame(animate);
        },
        
        updateCompletedSteps() {
            const steps = ['assets', 'welcome', 'database', 'admin', 'storage', 'complete'];
            const currentIndex = steps.indexOf(this.currentStep);
            
            this.completedSteps = steps.slice(0, currentIndex);
        }
    }
}
</script>