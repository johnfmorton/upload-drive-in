<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Provider Management') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('admin.cloud-storage.index') }}" 
                   class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                    {{ __('Back to Configuration') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="providerManagement()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-200 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Provider Overview Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @foreach($providersData as $provider)
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">
                                    {{ $provider['display_name'] ?? $provider['name'] }}
                                </h3>
                                <div class="flex items-center space-x-2">
                                    @if($provider['is_configured'] ?? false)
                                        @if($provider['has_connection'] ?? false)
                                            <span class="px-2 py-1 text-xs text-green-800 bg-green-100 rounded-full">
                                                Connected
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs text-yellow-800 bg-yellow-100 rounded-full">
                                                Configured
                                            </span>
                                        @endif
                                    @else
                                        <span class="px-2 py-1 text-xs text-gray-800 bg-gray-100 rounded-full">
                                            Not Configured
                                        </span>
                                    @endif
                                    
                                    @if($currentProvider === $provider['name'])
                                        <span class="px-2 py-1 text-xs text-blue-800 bg-blue-100 rounded-full">
                                            Current
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if(isset($provider['error']))
                                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded">
                                    <p class="text-sm text-red-600">{{ $provider['error'] }}</p>
                                </div>
                            @else
                                <div class="space-y-2 text-sm text-gray-600 mb-4">
                                    <div class="flex justify-between">
                                        <span>Auth Type:</span>
                                        <span class="font-medium">{{ ucfirst($provider['auth_type'] ?? 'Unknown') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Storage Model:</span>
                                        <span class="font-medium">{{ ucfirst($provider['storage_model'] ?? 'Unknown') }}</span>
                                    </div>
                                    @if(isset($provider['max_file_size']) && $provider['max_file_size'] > 0)
                                        <div class="flex justify-between">
                                            <span>Max File Size:</span>
                                            <span class="font-medium">{{ format_bytes($provider['max_file_size']) }}</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Health Status --}}
                                @if(isset($provider['health_status']))
                                    <div class="mb-4 p-3 rounded border
                                        @if($provider['health_status']->consolidated_status === 'healthy') bg-green-50 border-green-200
                                        @elseif($provider['health_status']->consolidated_status === 'authentication_required') bg-yellow-50 border-yellow-200
                                        @elseif($provider['health_status']->consolidated_status === 'connection_issues') bg-orange-50 border-orange-200
                                        @else bg-red-50 border-red-200
                                        @endif">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium">Health Status</span>
                                            <span class="text-xs
                                                @if($provider['health_status']->consolidated_status === 'healthy') text-green-600
                                                @elseif($provider['health_status']->consolidated_status === 'authentication_required') text-yellow-600
                                                @elseif($provider['health_status']->consolidated_status === 'connection_issues') text-orange-600
                                                @else text-red-600
                                                @endif">
                                                {{ ucfirst(str_replace('_', ' ', $provider['health_status']->consolidated_status)) }}
                                            </span>
                                        </div>
                                        @if($provider['health_status']->last_error_message)
                                            <p class="text-xs text-gray-600 mt-1">{{ $provider['health_status']->last_error_message }}</p>
                                        @endif
                                    </div>
                                @endif
                            @endif

                            {{-- Action Buttons --}}
                            <div class="flex flex-wrap gap-2">
                                @if(!isset($provider['error']))
                                    <button @click="openProviderDetails('{{ $provider['name'] }}')"
                                            class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                        Details
                                    </button>
                                    
                                    @if($provider['is_configured'] ?? false)
                                        <button @click="testProvider('{{ $provider['name'] }}')"
                                                :disabled="testingProvider === '{{ $provider['name'] }}' || isProviderRateLimited('{{ $provider['name'] }}')"
                                                :class="isProviderRateLimited('{{ $provider['name'] }}') ? 'px-3 py-1 text-sm bg-yellow-600 text-white rounded cursor-not-allowed opacity-75' : 'px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2'"
                                                class="disabled:opacity-50 disabled:cursor-not-allowed">
                                            <span x-show="testingProvider !== '{{ $provider['name'] }}' && !isProviderRateLimited('{{ $provider['name'] }}')">Test</span>
                                            <span x-show="testingProvider === '{{ $provider['name'] }}'" class="flex items-center">
                                                <svg class="animate-spin -ml-1 mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Testing
                                            </span>
                                            <span x-show="isProviderRateLimited('{{ $provider['name'] }}') && testingProvider !== '{{ $provider['name'] }}'" class="flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span x-text="'Rate Limited (' + getRateLimitCountdown('{{ $provider['name'] }}') + ')'"></span>
                                            </span>
                                        </button>
                                    @endif
                                    
                                    @if($currentProvider !== $provider['name'] && ($provider['is_configured'] ?? false))
                                        <button @click="setAsCurrentProvider('{{ $provider['name'] }}')"
                                                :disabled="settingProvider === '{{ $provider['name'] }}'"
                                                class="px-3 py-1 text-sm bg-purple-600 text-white rounded hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <span x-show="settingProvider !== '{{ $provider['name'] }}'">Set as Current</span>
                                            <span x-show="settingProvider === '{{ $provider['name'] }}'" class="flex items-center">
                                                <svg class="animate-spin -ml-1 mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Setting
                                            </span>
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Provider Details Modal --}}
            <div x-show="showDetailsModal" 
                 x-cloak
                 class="fixed inset-0 z-[9999] overflow-y-auto"
                 aria-labelledby="modal-title" 
                 role="dialog" 
                 aria-modal="true"
                 data-modal-name="provider-details"
                 data-z-index="9999"
                 data-modal-type="container">
                
                <!-- Background Overlay -->
                <div x-show="showDetailsModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 modal-backdrop transition-opacity z-[9998]"
                     @click="closeDetailsModal()"
                     data-modal-name="provider-details"
                     data-z-index="9998"
                     data-modal-type="backdrop"></div>

                <!-- Modal Panel -->
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div x-show="showDetailsModal"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full z-[10000] relative"
                         data-modal-name="provider-details"
                         data-z-index="10000"
                         data-modal-type="content">
                        
                        <!-- Modal Content -->
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900" x-text="selectedProvider?.display_name || 'Provider Details'"></h3>
                                <button @click="closeDetailsModal()" class="text-gray-400 hover:text-gray-600">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            <div x-show="loadingDetails" class="flex justify-center py-8">
                                <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            
                            <div x-show="!loadingDetails && selectedProvider" class="space-y-6">
                                <!-- Provider Information -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900 mb-2">Basic Information</h4>
                                        <dl class="space-y-1 text-sm">
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Name:</dt>
                                                <dd class="text-gray-900" x-text="selectedProvider?.name"></dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Authentication:</dt>
                                                <dd class="text-gray-900" x-text="selectedProvider?.auth_type"></dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Storage Model:</dt>
                                                <dd class="text-gray-900" x-text="selectedProvider?.storage_model"></dd>
                                            </div>
                                            <div class="flex justify-between" x-show="selectedProvider?.max_file_size">
                                                <dt class="text-gray-500">Max File Size:</dt>
                                                <dd class="text-gray-900" x-text="formatBytes(selectedProvider?.max_file_size)"></dd>
                                            </div>
                                        </dl>
                                    </div>
                                    
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900 mb-2">Status</h4>
                                        <dl class="space-y-1 text-sm">
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Configured:</dt>
                                                <dd class="text-gray-900">
                                                    <span x-show="selectedProvider?.is_configured" class="text-green-600">Yes</span>
                                                    <span x-show="!selectedProvider?.is_configured" class="text-red-600">No</span>
                                                </dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Connected:</dt>
                                                <dd class="text-gray-900">
                                                    <span x-show="selectedProvider?.has_connection" class="text-green-600">Yes</span>
                                                    <span x-show="!selectedProvider?.has_connection" class="text-red-600">No</span>
                                                </dd>
                                            </div>
                                            <div class="flex justify-between" x-show="selectedProvider?.health_status">
                                                <dt class="text-gray-500">Health:</dt>
                                                <dd class="text-gray-900" x-text="selectedProvider?.health_status?.consolidated_status?.replace('_', ' ')"></dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>

                                <!-- Capabilities -->
                                <div x-show="selectedProvider?.capabilities && Object.keys(selectedProvider.capabilities).length > 0">
                                    <h4 class="text-sm font-medium text-gray-900 mb-2">Capabilities</h4>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                        <template x-for="(value, capability) in selectedProvider?.capabilities" :key="capability">
                                            <div class="flex items-center space-x-2">
                                                <span x-show="value" class="text-green-500">✓</span>
                                                <span x-show="!value" class="text-red-500">✗</span>
                                                <span class="text-sm text-gray-700" x-text="capability.replace('_', ' ')"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Health Status Details -->
                                <div x-show="selectedProvider?.health_status">
                                    <h4 class="text-sm font-medium text-gray-900 mb-2">Health Status Details</h4>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <dl class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Status:</dt>
                                                <dd class="text-gray-900" x-text="selectedProvider?.health_status?.status"></dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Last Check:</dt>
                                                <dd class="text-gray-900" x-text="formatDate(selectedProvider?.health_status?.last_check)"></dd>
                                            </div>
                                            <div x-show="selectedProvider?.health_status?.last_error" class="flex flex-col space-y-1">
                                                <dt class="text-gray-500">Last Error:</dt>
                                                <dd class="text-red-600 text-xs" x-text="selectedProvider?.health_status?.last_error"></dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal Actions -->
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button @click="closeDetailsModal()"
                                    type="button"
                                    class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Test Results Modal --}}
            <div x-show="showTestModal" 
                 x-cloak
                 class="fixed inset-0 z-[9999] overflow-y-auto"
                 aria-labelledby="test-modal-title" 
                 role="dialog" 
                 aria-modal="true"
                 data-modal-name="test-results"
                 data-z-index="9999"
                 data-modal-type="container">
                
                <!-- Background Overlay -->
                <div x-show="showTestModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 modal-backdrop transition-opacity z-[9998]"
                     @click="closeTestModal()"
                     data-modal-name="test-results"
                     data-z-index="9998"
                     data-modal-type="backdrop"></div>

                <!-- Modal Panel -->
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div x-show="showTestModal"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-[10000] relative"
                         data-modal-name="test-results"
                         data-z-index="10000"
                         data-modal-type="content">
                        
                        <!-- Modal Content -->
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900" id="test-modal-title">Test Results</h3>
                                <button @click="closeTestModal()" class="text-gray-400 hover:text-gray-600">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3">
                                    <div x-show="testResult?.success" class="flex-shrink-0">
                                        <svg class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div x-show="!testResult?.success" class="flex-shrink-0">
                                        <svg class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-gray-900" x-text="testResult?.success ? 'Test Passed' : 'Test Failed'"></h4>
                                        <p class="text-sm text-gray-600" x-text="testResult?.message"></p>
                                    </div>
                                </div>
                                
                                <div x-show="testResult?.status_message" class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-sm text-gray-700" x-text="testResult?.status_message"></p>
                                </div>
                                
                                <div x-show="testResult?.last_successful_operation" class="text-xs text-gray-500">
                                    Last successful operation: <span x-text="formatDate(testResult?.last_successful_operation)"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal Actions -->
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button @click="closeTestModal()"
                                    type="button"
                                    class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function providerManagement() {
            return {
                showDetailsModal: false,
                showTestModal: false,
                loadingDetails: false,
                selectedProvider: null,
                testingProvider: null,
                settingProvider: null,
                testResult: null,
                rateLimitedProviders: {},
                rateLimitResetTimes: {},
                countdownInterval: null,

                async openProviderDetails(providerName) {
                    console.log('🔍 Opening provider details for:', providerName);
                    this.showDetailsModal = true;
                    this.loadingDetails = true;
                    this.selectedProvider = null;

                    try {
                        const response = await fetch(`/admin/cloud-storage/providers/${providerName}/details`);
                        const data = await response.json();

                        if (data.success) {
                            this.selectedProvider = data.provider;
                            console.log('🔍 Provider details loaded:', data.provider);
                        } else {
                            console.error('🔍 Failed to load provider details:', data.error);
                            this.showError('Failed to load provider details');
                            this.closeDetailsModal();
                        }
                    } catch (error) {
                        console.error('🔍 Error loading provider details:', error);
                        this.showError('Failed to load provider details');
                        this.closeDetailsModal();
                    } finally {
                        this.loadingDetails = false;
                    }
                },

                closeDetailsModal() {
                    console.log('🔍 Closing provider details modal');
                    this.showDetailsModal = false;
                    this.selectedProvider = null;
                    this.loadingDetails = false;
                },

                async testProvider(providerName) {
                    console.log('🔍 Testing provider with rate limiting protection:', providerName);
                    
                    // Check for rate limiting before attempting test
                    if (this.isProviderRateLimited(providerName)) {
                        const countdown = this.getRateLimitCountdown(providerName);
                        this.showError(`Rate limited. Please wait ${countdown} before testing again.`);
                        return;
                    }
                    
                    this.testingProvider = providerName;

                    try {
                        const response = await fetch('/admin/cloud-storage/test', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ provider: providerName })
                        });

                        const data = await response.json();
                        
                        // Handle rate limiting in response
                        if (response.status === 429 || data.error === 'Rate limit exceeded') {
                            this.handleRateLimitResponse(providerName, data);
                            return;
                        }
                        
                        this.testResult = data;
                        this.showTestModal = true;
                        
                        console.log('🔍 Test result:', data);
                    } catch (error) {
                        console.error('🔍 Error testing provider:', error);
                        
                        // Handle network errors that might indicate rate limiting
                        if (error.message.includes('429') || error.message.toLowerCase().includes('rate limit')) {
                            this.handleRateLimitResponse(providerName, { message: 'Rate limited. Please wait before testing again.' });
                        } else {
                            this.showError('Failed to test provider connection');
                        }
                    } finally {
                        this.testingProvider = null;
                    }
                },

                closeTestModal() {
                    console.log('🔍 Closing test modal');
                    this.showTestModal = false;
                    this.testResult = null;
                },

                async setAsCurrentProvider(providerName) {
                    console.log('🔍 Setting provider as current:', providerName);
                    this.settingProvider = providerName;

                    try {
                        const response = await fetch('/admin/cloud-storage/set-provider', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ provider: providerName })
                        });

                        const data = await response.json();

                        if (data.success) {
                            console.log('🔍 Provider set successfully');
                            this.showSuccess(data.message);
                            // Reload page to update current provider status
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            console.error('🔍 Failed to set provider:', data.error);
                            this.showError(data.error || 'Failed to set provider');
                        }
                    } catch (error) {
                        console.error('🔍 Error setting provider:', error);
                        this.showError('Failed to set provider');
                    } finally {
                        this.settingProvider = null;
                    }
                },

                formatBytes(bytes) {
                    if (!bytes || bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                },

                formatDate(dateString) {
                    if (!dateString) return 'Never';
                    try {
                        return new Date(dateString).toLocaleString();
                    } catch (e) {
                        return 'Invalid date';
                    }
                },

                // Rate limiting helper functions
                isProviderRateLimited(providerName) {
                    return this.rateLimitedProviders[providerName] || false;
                },

                getRateLimitCountdown(providerName) {
                    const resetTime = this.rateLimitResetTimes[providerName];
                    if (!resetTime) return 'soon';
                    
                    const now = new Date();
                    const diffMs = resetTime - now;
                    if (diffMs <= 0) {
                        // Rate limit has expired
                        this.rateLimitedProviders[providerName] = false;
                        delete this.rateLimitResetTimes[providerName];
                        return 'now';
                    }
                    
                    const minutes = Math.ceil(diffMs / 60000);
                    const seconds = Math.ceil(diffMs / 1000);
                    
                    if (seconds < 60) {
                        return `${seconds} second${seconds === 1 ? '' : 's'}`;
                    } else {
                        return `${minutes} minute${minutes === 1 ? '' : 's'}`;
                    }
                },

                handleRateLimitResponse(providerName, data) {
                    this.rateLimitedProviders[providerName] = true;
                    
                    // Set reset time if provided
                    if (data.retry_after) {
                        this.rateLimitResetTimes[providerName] = new Date(Date.now() + (data.retry_after * 1000));
                    } else if (data.reset_time) {
                        this.rateLimitResetTimes[providerName] = new Date(data.reset_time);
                    } else {
                        // Default to 5 minutes if no specific time provided
                        this.rateLimitResetTimes[providerName] = new Date(Date.now() + (5 * 60 * 1000));
                    }
                    
                    const countdown = this.getRateLimitCountdown(providerName);
                    this.showError(`Rate limited. Please wait ${countdown} before testing again.`);
                    
                    // Start countdown timer if not already running
                    if (!this.countdownInterval) {
                        this.startRateLimitCountdown();
                    }
                },

                startRateLimitCountdown() {
                    this.countdownInterval = setInterval(() => {
                        let hasActiveRateLimits = false;
                        
                        Object.keys(this.rateLimitedProviders).forEach(providerName => {
                            if (this.rateLimitedProviders[providerName]) {
                                const resetTime = this.rateLimitResetTimes[providerName];
                                if (resetTime && new Date() >= resetTime) {
                                    // Rate limit has expired
                                    this.rateLimitedProviders[providerName] = false;
                                    delete this.rateLimitResetTimes[providerName];
                                    console.log('🔍 Rate limit expired for provider:', providerName);
                                } else {
                                    hasActiveRateLimits = true;
                                }
                            }
                        });
                        
                        // Stop countdown if no active rate limits
                        if (!hasActiveRateLimits && this.countdownInterval) {
                            clearInterval(this.countdownInterval);
                            this.countdownInterval = null;
                        }
                    }, 1000);
                },

                showSuccess(message) {
                    // Simple success notification - you can enhance this
                    const notification = document.createElement('div');
                    notification.className = 'fixed top-4 right-4 bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded z-50';
                    notification.textContent = message;
                    document.body.appendChild(notification);
                    setTimeout(() => notification.remove(), 3000);
                },

                showError(message) {
                    // Simple error notification - you can enhance this
                    const notification = document.createElement('div');
                    notification.className = 'fixed top-4 right-4 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded z-50';
                    notification.textContent = message;
                    document.body.appendChild(notification);
                    setTimeout(() => notification.remove(), 5000);
                }
            };
        }
    </script>
</x-app-layout>