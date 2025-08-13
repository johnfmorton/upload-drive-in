@props([
    'error' => null,
    'title' => 'Setup Error',
    'showTechnicalDetails' => false
])

@if($error)
<div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.962-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
        </div>
        <div class="ml-3 flex-1">
            <h3 class="text-lg font-medium text-red-800 mb-2">{{ $title }}</h3>
            
            <!-- User-friendly error message -->
            <div class="text-red-700 mb-4">
                {{ $error['user_message'] ?? 'An error occurred during setup.' }}
            </div>

            <!-- Troubleshooting steps -->
            @if(!empty($error['troubleshooting_steps']))
            <div class="mb-4">
                <h4 class="text-md font-medium text-red-800 mb-2">Troubleshooting Steps:</h4>
                <ol class="list-decimal list-inside space-y-1 text-sm text-red-700">
                    @foreach($error['troubleshooting_steps'] as $step)
                    <li>{{ $step }}</li>
                    @endforeach
                </ol>
            </div>
            @endif

            <!-- Recovery suggestions -->
            @if(!empty($error['recovery_suggestions']))
            <div class="mb-4">
                <h4 class="text-md font-medium text-red-800 mb-2">What to try next:</h4>
                <ul class="list-disc list-inside space-y-1 text-sm text-red-700">
                    @foreach($error['recovery_suggestions'] as $suggestion)
                    <li>{{ $suggestion }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Documentation links -->
            @if(!empty($error['documentation_links']))
            <div class="mb-4">
                <h4 class="text-md font-medium text-red-800 mb-2">Helpful Resources:</h4>
                <ul class="space-y-1">
                    @foreach($error['documentation_links'] as $link)
                    <li>
                        <a href="{{ $link['url'] }}" 
                           class="text-red-600 hover:text-red-800 underline text-sm"
                           @if(str_starts_with($link['url'], 'http')) target="_blank" rel="noopener noreferrer" @endif>
                            {{ $link['title'] }}
                        </a>
                        @if(!empty($link['description']))
                        <span class="text-red-600 text-sm"> - {{ $link['description'] }}</span>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Technical details toggle -->
            @if(!empty($error['technical_message']) && $error['technical_message'] !== $error['user_message'])
            <div class="mt-4">
                <button type="button" 
                        onclick="toggleTechnicalDetails()"
                        class="text-sm text-red-600 hover:text-red-800 underline focus:outline-none">
                    <span id="technical-toggle-text">Show technical details</span>
                </button>
                
                <div id="technical-details" class="hidden mt-2 p-3 bg-red-100 rounded border text-sm">
                    <strong class="text-red-800">Technical Error:</strong>
                    <div class="text-red-700 font-mono text-xs mt-1 break-all">
                        {{ $error['technical_message'] }}
                    </div>
                    
                    @if(!empty($error['error_context']))
                    <div class="mt-2">
                        <strong class="text-red-800">Context:</strong>
                        <div class="text-red-700 text-xs mt-1">
                            @foreach($error['error_context'] as $key => $value)
                            <div><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ is_array($value) ? json_encode($value) : $value }}</div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Action buttons -->
            <div class="mt-4 flex space-x-3">
                <button type="button" 
                        onclick="window.location.reload()"
                        class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Try Again
                </button>
                
                @if(!empty($error['recovery_suggestions']))
                <button type="button" 
                        onclick="showRecoveryHelp()"
                        class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Get Help
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function toggleTechnicalDetails() {
    const details = document.getElementById('technical-details');
    const toggleText = document.getElementById('technical-toggle-text');
    
    if (details.classList.contains('hidden')) {
        details.classList.remove('hidden');
        toggleText.textContent = 'Hide technical details';
    } else {
        details.classList.add('hidden');
        toggleText.textContent = 'Show technical details';
    }
}

function showRecoveryHelp() {
    // Scroll to recovery suggestions if they exist
    const recoverySection = document.querySelector('h4:contains("What to try next:")');
    if (recoverySection) {
        recoverySection.scrollIntoView({ behavior: 'smooth' });
    }
}
</script>
@endif