@props([
    'title' => 'Setup Complete!',
    'message' => 'Congratulations! Your installation is now ready to use.',
    'nextSteps' => [],
    'autoRedirect' => true,
    'redirectUrl' => '',
    'redirectDelay' => 10000
])

<div x-data="setupCelebration()" 
     x-show="show"
     class="fixed inset-0 z-50 flex items-center justify-center bg-gradient-to-br from-blue-900 via-purple-900 to-indigo-900">
    
    <!-- Confetti Animation Background -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="confetti-container">
            @for($i = 0; $i < 50; $i++)
            <div class="confetti confetti-{{ $i % 5 + 1 }}" style="left: {{ rand(0, 100) }}%; animation-delay: {{ rand(0, 3000) }}ms;"></div>
            @endfor
        </div>
    </div>

    <!-- Main Celebration Content -->
    <div class="relative bg-white rounded-2xl shadow-2xl p-8 max-w-2xl mx-4 text-center">
        <!-- Animated Success Icon -->
        <div class="flex justify-center mb-6">
            <div class="relative">
                <div class="w-24 h-24 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center shadow-lg">
                    <svg class="w-12 h-12 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <!-- Glow effect -->
                <div class="absolute inset-0 w-24 h-24 bg-green-400 rounded-full animate-ping opacity-20"></div>
            </div>
        </div>

        <!-- Celebration Title -->
        <h1 class="text-4xl font-bold text-gray-900 mb-4 animate-bounce">
            🎉 {{ $title }}
        </h1>

        <!-- Success Message -->
        <p class="text-xl text-gray-600 mb-8">
            {{ $message }}
        </p>

        <!-- Setup Summary Stats -->
        <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="bg-green-50 rounded-lg p-4">
                <div class="text-2xl font-bold text-green-600" x-text="completedSteps">5</div>
                <div class="text-sm text-green-700">Steps Completed</div>
            </div>
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="text-2xl font-bold text-blue-600" x-text="setupTime">2:34</div>
                <div class="text-sm text-blue-700">Setup Time</div>
            </div>
            <div class="bg-purple-50 rounded-lg p-4">
                <div class="text-2xl font-bold text-purple-600">100%</div>
                <div class="text-sm text-purple-700">Complete</div>
            </div>
        </div>

        <!-- Next Steps -->
        @if(!empty($nextSteps))
        <div class="text-left mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">What's Next?</h3>
            <div class="space-y-3">
                @foreach($nextSteps as $index => $step)
                <div class="flex items-start p-3 bg-gray-50 rounded-lg" 
                     x-show="showStep{{ $index }}"
                     x-transition:enter="transition ease-out duration-300 delay-{{ $index * 200 }}ms"
                     x-transition:enter-start="opacity-0 translate-x-4"
                     x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                        <span class="text-sm font-medium text-blue-600">{{ $index + 1 }}</span>
                    </div>
                    <div class="text-sm text-gray-700">{{ $step }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($autoRedirect && $redirectUrl)
        <!-- Auto-redirect countdown -->
        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
            <div class="flex items-center justify-center text-blue-700">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Redirecting to admin dashboard in <strong x-text="countdown">{{ floor($redirectDelay / 1000) }}</strong> seconds</span>
            </div>
            <div class="mt-2 w-full bg-blue-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-1000 ease-linear" 
                     @if($autoRedirect && $redirectUrl)
                     :style="`width: ${(1 - countdown / {{ floor($redirectDelay / 1000) }}) * 100}%`"
                     @endif></div>
            </div>
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            @if($redirectUrl)
            <a href="{{ $redirectUrl }}" 
               class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                <svg class="mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                Go to Admin Dashboard
            </a>
            @endif
            
            <button @click="show = false" 
                    class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                <svg class="mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Close
            </button>
        </div>
    </div>
</div>

<!-- Confetti Styles -->
<style>
.confetti-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.confetti {
    position: absolute;
    width: 10px;
    height: 10px;
    animation: confetti-fall 3s linear infinite;
}

.confetti-1 { background: #f43f5e; }
.confetti-2 { background: #3b82f6; }
.confetti-3 { background: #10b981; }
.confetti-4 { background: #f59e0b; }
.confetti-5 { background: #8b5cf6; }

@keyframes confetti-fall {
    0% {
        transform: translateY(-100vh) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(100vh) rotate(720deg);
        opacity: 0;
    }
}
</style>

<script>
function setupCelebration() {
    return {
        show: true,
        completedSteps: 5,
        setupTime: '2:34',
        @if($autoRedirect && $redirectUrl)
        countdown: {{ floor($redirectDelay / 1000) }},
        @endif
        @foreach($nextSteps as $index => $step)
        showStep{{ $index }}: false,
        @endforeach
        
        init() {
            // Animate next steps appearance
            @foreach($nextSteps as $index => $step)
            setTimeout(() => {
                this.showStep{{ $index }} = true;
            }, {{ ($index + 1) * 500 }});
            @endforeach
            
            @if($autoRedirect && $redirectUrl)
            // Start countdown
            const countdownInterval = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = '{{ $redirectUrl }}';
                }
            }, 1000);
            @endif
        }
    }
}
</script>