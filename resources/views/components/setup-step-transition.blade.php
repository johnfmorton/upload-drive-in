@props([
    'fromStep' => '',
    'toStep' => '',
    'message' => '',
    'duration' => 2000
])

<div x-data="stepTransition()" 
     x-show="show" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    
    <div class="bg-white rounded-lg shadow-xl p-8 max-w-md mx-4">
        <!-- Success Icon with Animation -->
        <div class="flex justify-center mb-6">
            <div class="relative">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <!-- Ripple effect -->
                <div class="absolute inset-0 w-16 h-16 bg-green-200 rounded-full animate-ping opacity-75"></div>
            </div>
        </div>

        <!-- Step Transition Message -->
        <div class="text-center">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Step Complete!</h3>
            <p class="text-gray-600 mb-4">
                {{ $message ?: "Moving to the next step..." }}
            </p>
            
            <!-- Step Progress Indicator -->
            <div class="flex items-center justify-center space-x-2 text-sm text-gray-500">
                <span class="px-2 py-1 bg-green-100 text-green-700 rounded">{{ ucfirst($fromStep) }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded">{{ ucfirst($toStep) }}</span>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-6">
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-1000 ease-out" 
                     :style="`width: ${progress}%`"></div>
            </div>
        </div>
    </div>
</div>

<script>
function stepTransition() {
    return {
        show: true,
        progress: 0,
        
        init() {
            // Animate progress bar
            setTimeout(() => {
                this.progress = 100;
            }, 100);
            
            // Auto-hide after duration
            setTimeout(() => {
                this.show = false;
            }, {{ $duration }});
        }
    }
}
</script>