@props([
    'step' => '',
    'title' => '',
    'message' => '',
    'details' => [],
    'nextStep' => '',
    'nextStepUrl' => '',
    'showProgress' => true,
    'progress' => 0,
    'autoAdvance' => false,
    'autoAdvanceDelay' => 3000
])

<div x-data="stepCompletion()" 
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
    
    <!-- Success Header -->
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <!-- Animated checkmark -->
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>
        
        <div class="ml-4 flex-1">
            <!-- Step completion title -->
            <h3 class="text-lg font-semibold text-green-800 mb-2">
                {{ $title ?: ucfirst($step) . ' Step Complete!' }}
            </h3>
            
            <!-- Success message -->
            <p class="text-green-700 mb-4">
                {{ $message ?: 'This step has been completed successfully. You can now proceed to the next step.' }}
            </p>
            
            <!-- Progress indicator -->
            @if($showProgress && $progress > 0)
            <div class="mb-4">
                <div class="flex items-center justify-between text-sm text-green-700 mb-2">
                    <span class="font-medium">Overall Progress</span>
                    <span class="font-medium" x-text="`${animatedProgress}%`">{{ $progress }}%</span>
                </div>
                <div class="w-full bg-green-200 rounded-full h-3 overflow-hidden">
                    <div class="bg-green-600 h-3 rounded-full transition-all duration-1000 ease-out"
                         :style="`width: ${animatedProgress}%`">
                        <!-- Shimmer effect -->
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-30 animate-pulse"></div>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Completion details -->
            @if(!empty($details))
            <div class="mb-4">
                <h4 class="text-sm font-medium text-green-800 mb-2">What was completed:</h4>
                <ul class="space-y-1">
                    @foreach($details as $index => $detail)
                    <li class="flex items-start text-sm text-green-700"
                        x-show="showDetail{{ $index }}"
                        x-transition:enter="transition ease-out duration-300 delay-{{ $index * 200 }}ms"
                        x-transition:enter-start="opacity-0 translate-x-2"
                        x-transition:enter-end="opacity-100 translate-x-0">
                        <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ $detail }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
            
            <!-- Next step information -->
            @if($nextStep)
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <div class="text-sm font-medium text-blue-800">
                            Next: {{ ucfirst($nextStep) }}
                        </div>
                        @if($autoAdvance && $nextStepUrl)
                        <div class="text-xs text-blue-600">
                            Automatically continuing in <span x-text="countdown">{{ floor($autoAdvanceDelay / 1000) }}</span> seconds...
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Action buttons -->
            <div class="flex items-center justify-between">
                <div class="flex items-center text-sm text-green-600">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="font-medium">Step completed successfully</span>
                </div>
                
                @if($nextStepUrl)
                <div class="flex space-x-3">
                    @if($autoAdvance)
                    <button @click="cancelAutoAdvance()" 
                            class="text-sm text-gray-600 hover:text-gray-800 underline">
                        Cancel auto-advance
                    </button>
                    @endif
                    
                    <a href="{{ $nextStepUrl }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        Continue to {{ ucfirst($nextStep) }}
                        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function stepCompletion() {
    return {
        show: true,
        animatedProgress: 0,
        countdown: {{ floor($autoAdvanceDelay / 1000) }},
        autoAdvanceTimer: null,
        countdownTimer: null,
        @foreach($details as $index => $detail)
        showDetail{{ $index }}: false,
        @endforeach
        
        init() {
            // Animate progress
            this.animateProgress({{ $progress }});
            
            // Show details with staggered animation
            @foreach($details as $index => $detail)
            setTimeout(() => {
                this.showDetail{{ $index }} = true;
            }, {{ ($index + 1) * 300 }});
            @endforeach
            
            @if($autoAdvance && $nextStepUrl)
            // Start auto-advance countdown
            this.startAutoAdvance();
            @endif
        },
        
        animateProgress(targetProgress) {
            const duration = 1000;
            const startTime = Date.now();
            const startProgress = this.animatedProgress;
            
            const animate = () => {
                const elapsed = Date.now() - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeOutCubic = 1 - Math.pow(1 - progress, 3);
                
                this.animatedProgress = Math.round(startProgress + (targetProgress - startProgress) * easeOutCubic);
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            };
            
            requestAnimationFrame(animate);
        },
        
        startAutoAdvance() {
            this.countdownTimer = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    this.clearTimers();
                    window.location.href = '{{ $nextStepUrl }}';
                }
            }, 1000);
        },
        
        cancelAutoAdvance() {
            this.clearTimers();
            this.countdown = 0;
        },
        
        clearTimers() {
            if (this.countdownTimer) {
                clearInterval(this.countdownTimer);
                this.countdownTimer = null;
            }
            if (this.autoAdvanceTimer) {
                clearTimeout(this.autoAdvanceTimer);
                this.autoAdvanceTimer = null;
            }
        }
    }
}
</script>