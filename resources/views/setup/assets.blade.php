<x-setup-layout 
    :title="'Asset Build'" 
    :current-step="1" 
    :total-steps="6" 
    :steps="['Assets', 'Welcome', 'Database', 'Admin User', 'Cloud Storage', 'Complete']">

    <div class="p-8" data-setup-step="assets">
        <!-- Progress Indicator -->
        <x-setup-progress-indicator 
            :current-step="$currentStep ?? 'assets'" 
            :progress="$progress ?? 10" />

        <!-- Success Message -->
        @if(session('success'))
            <x-setup-success-display 
                :message="session('success')" 
                :show-progress="true"
                :progress="$progress ?? 10" />
        @endif

        <!-- Error Display -->
        @if(session('setup_error'))
            <x-setup-error-display 
                :error="session('setup_error')" 
                title="Asset Build Error" />
        @endif

        <!-- Header -->
        <div class="text-center mb-8">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-orange-100 mb-4">
                <svg class="h-8 w-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 7.172V5L8 4z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Build Frontend Assets</h2>
            <p class="text-gray-600 max-w-md mx-auto">
                Before we can start the setup process, we need to build the frontend assets. This ensures all styles and JavaScript are properly compiled.
            </p>
        </div>

        <!-- Asset Build Status -->
        <div class="mb-8">
            <x-asset-build-status :status="$assetStatus ?? []" />
        </div>

        <!-- Build Instructions -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Build Instructions</h3>
            
            <!-- Step-by-step instructions -->
            <div class="space-y-6">
                <!-- Step 1: Install Dependencies -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-medium">
                                1
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h4 class="text-lg font-medium text-gray-900 mb-2">Install Node.js Dependencies</h4>
                            <p class="text-gray-600 mb-4">
                                First, install all required Node.js packages using npm. This will download all the necessary dependencies for building the frontend assets.
                            </p>
                            
                            <x-copy-command-block 
                                command="npm ci" 
                                description="Install dependencies from package-lock.json" />
                            
                            <div class="mt-3 text-sm text-gray-500">
                                <p><strong>Note:</strong> We use <code class="bg-gray-200 px-1 rounded">npm ci</code> instead of <code class="bg-gray-200 px-1 rounded">npm install</code> for faster, reliable, reproducible builds.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Build Assets -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-medium">
                                2
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h4 class="text-lg font-medium text-gray-900 mb-2">Build Production Assets</h4>
                            <p class="text-gray-600 mb-4">
                                Build and optimize all CSS and JavaScript files for production use. This creates the manifest file that the application needs to load assets properly.
                            </p>
                            
                            <x-copy-command-block 
                                command="npm run build" 
                                description="Build and optimize assets for production" />
                            
                            <div class="mt-3 text-sm text-gray-500">
                                <p><strong>Expected output:</strong> This will create files in the <code class="bg-gray-200 px-1 rounded">public/build/</code> directory including <code class="bg-gray-200 px-1 rounded">manifest.json</code>.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Verify Build -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-medium">
                                3
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h4 class="text-lg font-medium text-gray-900 mb-2">Verify Build Completion</h4>
                            <p class="text-gray-600 mb-4">
                                After building, refresh this page to verify that the assets were built successfully and continue with the setup process.
                            </p>
                            
                            <button type="button" 
                                    onclick="checkAssetStatus()" 
                                    id="check-assets-btn"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Check Asset Status
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Troubleshooting Section -->
        <div class="mb-8">
            <x-asset-troubleshooting />
        </div>

        <!-- Help Panel -->
        <div class="mb-8">
            <x-setup-help-panel step="assets" />
        </div>

        <!-- Navigation -->
        <div class="flex justify-between items-center pt-6 border-t border-gray-200">
            <div class="text-sm text-gray-500">
                Complete the build steps above to continue
            </div>
            <div class="flex space-x-3">
                <button type="button" 
                        onclick="window.location.reload()" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh Page
                </button>
                
                @if($assetsBuilt ?? false)
                    <a href="{{ route('setup.welcome') }}" 
                       class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Continue Setup
                        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                @endif
            </div>
        </div>
    </div>

</x-setup-layout>

@push('scripts')
<script>
async function checkAssetStatus() {
    const btn = document.getElementById('check-assets-btn');
    const originalText = btn.innerHTML;
    
    // Show loading state
    btn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Checking...
    `;
    btn.disabled = true;
    
    try {
        const response = await fetch('{{ route("setup.ajax.check-assets") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.assets_built) {
            // Assets are built, reload the page to show continue button
            window.location.reload();
        } else {
            // Assets not built yet, show message
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            // Show temporary message
            const message = document.createElement('div');
            message.className = 'mt-2 text-sm text-orange-600';
            message.textContent = 'Assets not ready yet. Please complete the build steps above.';
            btn.parentNode.appendChild(message);
            
            setTimeout(() => {
                message.remove();
            }, 3000);
        }
    } catch (error) {
        console.error('Error checking asset status:', error);
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
</script>
@endpush