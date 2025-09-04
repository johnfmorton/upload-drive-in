@props(['user', 'isAdmin' => false])

@php
    use App\Services\CloudStorageHealthService;
    use App\Services\CloudStorageErrorMessageService;
    use App\Models\FileUpload;
    
    $healthService = app(CloudStorageHealthService::class);
    $errorMessageService = app(CloudStorageErrorMessageService::class);
    $providersHealth = $healthService->getAllProvidersHealth($user);
    
    // Get pending uploads count for each provider
    $pendingUploads = FileUpload::where(function($query) use ($user) {
        $query->where('company_user_id', $user->id)
              ->orWhere('uploaded_by_user_id', $user->id);
    })
    ->whereNull('google_drive_file_id')
    ->whereNull('cloud_storage_error_type')
    ->get()
    ->groupBy('cloud_storage_provider');
    
    // Get failed uploads count for each provider
    $failedUploads = FileUpload::where(function($query) use ($user) {
        $query->where('company_user_id', $user->id)
              ->orWhere('uploaded_by_user_id', $user->id);
    })
    ->whereNotNull('cloud_storage_error_type')
    ->get()
    ->groupBy('cloud_storage_provider');
@endphp

<div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg" 
     x-data="cloudStorageStatusWidget({{ json_encode($providersHealth->toArray()) }})"
     x-init="initializeWidget()"
     x-on:visibility-change.window="handleVisibilityChange($event)"
     x-on:focus.window="handleWindowFocus()"
     x-on:online.window="handleOnlineStatus(true)"
     x-on:offline.window="handleOnlineStatus(false)"
     x-on:beforeunload.window="destroy()">
    
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Cloud Storage Status') }}
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                {{ __('Monitor your cloud storage connections and manage uploads') }}
            </p>
        </div>
        
        <!-- Refresh Button -->
        <button @click="refreshStatus()" 
                :disabled="isRefreshing"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
            <svg x-show="!isRefreshing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <svg x-show="isRefreshing" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span x-text="isRefreshing ? '{{ __('Refreshing...') }}' : '{{ __('Refresh') }}'"></span>
        </button>
    </div>

    <!-- Provider Status Cards -->
    <div class="grid grid-cols-1  gap-6">
        <template x-for="provider in providers" :key="provider.provider">
            <div class="border border-gray-200 rounded-lg p-4">
                <!-- Provider Header -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <!-- Provider Icon -->
                        <div class="flex-shrink-0 mr-3">
                            <template x-if="provider.provider === 'google-drive'">
                                <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/>
                                </svg>
                            </template>
                            <!-- Add more provider icons as needed -->
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-medium text-gray-900" x-text="getProviderDisplayName(provider.provider)"></h3>
                            <p class="text-xs text-gray-500" x-text="provider.status_message || getConsolidatedStatusMessage(provider.consolidated_status)"></p>
                        </div>
                    </div>
                    
                    <!-- Enhanced Status Indicator -->
                    <div class="flex items-center">
                        <div class="flex items-center mr-2">
                            <!-- Animated status indicator with pulse for active states -->
                            <div class="relative">
                                <div :class="getStatusIndicatorClass(provider.consolidated_status || provider.status)" 
                                     class="w-3 h-3 rounded-full mr-2 transition-all duration-300"></div>
                                <!-- Pulse animation for active/processing states -->
                                <div x-show="isProviderProcessing(provider.provider)" 
                                     :class="getStatusIndicatorClass(provider.consolidated_status || provider.status)" 
                                     class="absolute top-0 left-0 w-3 h-3 rounded-full mr-2 animate-ping opacity-75"></div>
                            </div>
                            <span class="text-sm font-medium transition-colors duration-300" 
                                  :class="getStatusTextClass(provider.consolidated_status || provider.status)" 
                                  x-text="getStatusDisplayText(provider.consolidated_status || provider.status)"></span>
                            <!-- Connection quality indicator -->
                            <div x-show="provider.connection_quality" class="ml-2">
                                <div :class="getConnectionQualityClass(provider.connection_quality)" 
                                     class="w-2 h-2 rounded-full" 
                                     :title="getConnectionQualityTooltip(provider.connection_quality)"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Connection Details -->
                <div class="space-y-3">
                    <!-- Connection Health Summary -->
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Connection Health:</span>
                        <div class="flex items-center">
                            <div :class="getConnectionHealthClass(provider)" class="w-2 h-2 rounded-full mr-1"></div>
                            <span :class="getConnectionHealthTextClass(provider)" 
                                  x-text="getConnectionHealthText(provider)"></span>
                        </div>
                    </div>

                    <!-- Last Successful Operation -->
                    <div x-show="provider.last_successful_operation_at" class="flex items-center text-sm text-gray-600">
                        <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Last success: <span x-text="formatTimestamp(provider.last_successful_operation_at)"></span></span>
                    </div>

                    <!-- Token Status -->
                    <div x-show="provider.token_status" class="flex items-center text-sm">
                        <svg :class="getTokenStatusIconClass(provider.token_status)" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                        <span :class="getTokenStatusTextClass(provider.token_status)" x-text="getTokenStatusText(provider.token_status)"></span>
                    </div>

                    <!-- Enhanced Error Information with Actionable Messages -->
                    <div x-show="provider.last_error_message" class="p-3 bg-red-50 border border-red-200 rounded-md">
                        <div class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="flex-1">
                                <div class="font-medium text-red-800 text-sm">Connection Issue</div>
                                <div class="text-red-700 text-sm mt-1" x-text="getActionableErrorMessage(provider)"></div>
                                <!-- Recovery Instructions -->
                                <div x-show="getRecoveryInstructions(provider)" class="mt-2">
                                    <div class="text-red-600 text-xs font-medium">Recommended Action:</div>
                                    <div class="text-red-600 text-xs" x-text="getRecoveryInstructions(provider)"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rate Limiting Information -->
                    <div x-show="provider.is_rate_limited" class="flex items-center text-sm text-yellow-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Rate limited - next check in <span x-text="getRateLimitResetTime(provider)"></span></span>
                    </div>

                    <!-- Consecutive Failures with Trend -->
                    <div x-show="provider.consecutive_failures > 0" class="flex items-center justify-between text-sm text-orange-600">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span x-text="`${provider.consecutive_failures} consecutive failure${provider.consecutive_failures === 1 ? '' : 's'}`"></span>
                        </div>
                        <div class="flex items-center">
                            <div :class="getFailureTrendClass(provider.failure_trend)" class="w-2 h-2 rounded-full mr-1"></div>
                            <span class="text-xs" x-text="getFailureTrendText(provider.failure_trend)"></span>
                        </div>
                    </div>
                </div>

                <!-- Upload Statistics -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="text-center">
                            <div class="text-lg font-semibold text-gray-900" x-text="getPendingCount(provider.provider)"></div>
                            <div class="text-gray-500">Pending</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-semibold text-red-600" x-text="getFailedCount(provider.provider)"></div>
                            <div class="text-gray-500">Failed</div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-4 pt-4 border-t border-gray-200 flex flex-wrap gap-2">
                    <!-- Reconnect Button -->
                    <template x-if="(provider.consolidated_status && (provider.consolidated_status === 'authentication_required' || provider.consolidated_status === 'not_connected')) || provider.requires_reconnection || provider.is_disconnected">
                        <button @click="reconnectProvider(provider.provider)"
                                :disabled="isReconnecting[provider.provider]"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="!isReconnecting[provider.provider]" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <svg x-show="isReconnecting[provider.provider]" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isReconnecting[provider.provider] ? '{{ __('Connecting...') }}' : '{{ __('Reconnect') }}'"></span>
                        </button>
                    </template>

                    <!-- Retry Failed Uploads Button -->
                    <template x-if="getFailedCount(provider.provider) > 0">
                        <button @click="retryFailedUploads(provider.provider)"
                                :disabled="isRetrying[provider.provider]"
                                class="inline-flex items-center px-3 py-2 border border-orange-300 text-sm font-medium rounded-md text-orange-700 bg-white hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="!isRetrying[provider.provider]" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <svg x-show="isRetrying[provider.provider]" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isRetrying[provider.provider] ? '{{ __('Retrying...') }}' : `{{ __('Retry') }} (${getFailedCount(provider.provider)})`"></span>
                        </button>
                    </template>

                    <!-- Test Connection Button -->
                    <template x-if="(provider.consolidated_status && (provider.consolidated_status === 'healthy' || provider.consolidated_status === 'connection_issues')) || provider.is_healthy || provider.is_degraded">
                        <button @click="testConnection(provider.provider)"
                                :disabled="isTesting[provider.provider]"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="!isTesting[provider.provider]" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <svg x-show="isTesting[provider.provider]" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isTesting[provider.provider] ? '{{ __('Testing...') }}' : '{{ __('Test Connection') }}'"></span>
                        </button>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- Overall Status Summary -->
    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-medium text-gray-900">Overall Status</h3>
                <p class="text-sm text-gray-600" x-text="getOverallStatusMessage()"></p>
            </div>
            <div class="flex items-center">
                <div :class="getOverallStatusIndicatorClass()" class="w-3 h-3 rounded-full mr-2"></div>
                <span class="text-sm font-medium" :class="getOverallStatusTextClass()" x-text="getOverallStatusText()"></span>
            </div>
        </div>
    </div>
</div>

<script>
function cloudStorageStatusWidget(initialProviders) {
    return {
        providers: initialProviders,
        isRefreshing: false,
        isReconnecting: {},
        isRetrying: {},
        isTesting: {},
        isOnline: navigator.onLine,
        lastRefreshTime: Date.now(),
        refreshInterval: null,
        errorCount: 0,
        maxRetries: 3,
        backoffMultiplier: 1,
        pendingUploads: @json($pendingUploads->map(fn($uploads) => $uploads->count())->toArray()),
        failedUploads: @json($failedUploads->map(fn($uploads) => $uploads->count())->toArray()),
        
        initializeWidget() {
            console.log('🔍 Cloud Storage Status Widget initialized with enhanced features');
            this.initializeLoadingStates();
            this.startPeriodicRefresh();
            this.setupVisibilityHandling();
            this.validateInitialStatus();
        },
        
        initializeLoadingStates() {
            this.providers.forEach(provider => {
                this.isReconnecting[provider.provider] = false;
                this.isRetrying[provider.provider] = false;
                this.isTesting[provider.provider] = false;
            });
        },
        
        startPeriodicRefresh() {
            // Dynamic refresh interval based on connection health
            const getRefreshInterval = () => {
                const hasUnhealthyProviders = this.providers.some(p => 
                    (p.consolidated_status && ['authentication_required', 'connection_issues'].includes(p.consolidated_status)) ||
                    (!p.consolidated_status && (p.is_unhealthy || p.is_disconnected))
                );
                return hasUnhealthyProviders ? 15000 : 30000; // 15s for unhealthy, 30s for healthy
            };
            
            const scheduleNextRefresh = () => {
                if (this.refreshInterval) {
                    clearTimeout(this.refreshInterval);
                }
                
                this.refreshInterval = setTimeout(() => {
                    if (!this.isRefreshing && this.isOnline && !document.hidden) {
                        this.refreshStatus(true).finally(() => {
                            scheduleNextRefresh();
                        });
                    } else {
                        scheduleNextRefresh();
                    }
                }, getRefreshInterval());
            };
            
            scheduleNextRefresh();
        },
        
        setupVisibilityHandling() {
            // Handle page visibility changes
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && this.isOnline) {
                    // Page became visible, refresh if it's been more than 30 seconds
                    if (Date.now() - this.lastRefreshTime > 30000) {
                        this.refreshStatus(true);
                    }
                }
            });
        },
        
        validateInitialStatus() {
            // Validate that initial status makes sense
            this.providers.forEach(provider => {
                if (!provider.consolidated_status && !provider.status) {
                    console.warn('🔍 Provider missing status information:', provider.provider);
                }
            });
        },
        
        async refreshStatus(silent = false) {
            if (!this.isOnline) {
                if (!silent) {
                    this.showError('Cannot refresh status while offline');
                }
                return;
            }
            
            if (!silent) {
                console.log('🔍 Refreshing cloud storage status with enhanced validation');
                this.isRefreshing = true;
            }
            
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
                
                const response = await fetch('{{ $isAdmin ? route('admin.cloud-storage.status') : route('employee.cloud-storage.status', ['username' => $user->username]) }}', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                
                if (response.ok) {
                    const data = await response.json();
                    
                    // Validate response structure
                    if (!data.providers || !Array.isArray(data.providers)) {
                        throw new Error('Invalid response structure');
                    }
                    
                    // Update data with validation
                    this.updateProvidersData(data);
                    this.lastRefreshTime = Date.now();
                    this.errorCount = 0; // Reset error count on success
                    this.backoffMultiplier = 1; // Reset backoff
                    
                    if (!silent) {
                        console.log('🔍 Status refreshed successfully with', data.providers.length, 'providers');
                    }
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            } catch (error) {
                this.handleRefreshError(error, silent);
            } finally {
                if (!silent) {
                    this.isRefreshing = false;
                }
            }
        },
        
        updateProvidersData(data) {
            // Preserve loading states when updating provider data
            const currentLoadingStates = {};
            this.providers.forEach(provider => {
                currentLoadingStates[provider.provider] = {
                    isReconnecting: this.isReconnecting[provider.provider] || false,
                    isRetrying: this.isRetrying[provider.provider] || false,
                    isTesting: this.isTesting[provider.provider] || false
                };
            });
            
            this.providers = data.providers;
            this.pendingUploads = data.pending_uploads || {};
            this.failedUploads = data.failed_uploads || {};
            
            // Restore loading states
            Object.keys(currentLoadingStates).forEach(provider => {
                this.isReconnecting[provider] = currentLoadingStates[provider].isReconnecting;
                this.isRetrying[provider] = currentLoadingStates[provider].isRetrying;
                this.isTesting[provider] = currentLoadingStates[provider].isTesting;
            });
        },
        
        handleRefreshError(error, silent) {
            this.errorCount++;
            console.error('🔍 Failed to refresh status (attempt', this.errorCount, '):', error);
            
            if (error.name === 'AbortError') {
                if (!silent) {
                    this.showError('Request timed out. Please check your connection.');
                }
            } else if (!this.isOnline) {
                if (!silent) {
                    this.showError('Connection lost. Status will refresh when online.');
                }
            } else if (this.errorCount >= this.maxRetries) {
                if (!silent) {
                    this.showError('Failed to refresh status after multiple attempts. Please refresh the page.');
                }
                // Stop automatic refreshes after max retries
                if (this.refreshInterval) {
                    clearTimeout(this.refreshInterval);
                }
            } else {
                // Exponential backoff for retries
                this.backoffMultiplier = Math.min(this.backoffMultiplier * 2, 8);
                if (!silent) {
                    this.showError(`Failed to refresh status. Retrying in ${this.backoffMultiplier * 5} seconds...`);
                }
            }
        },
        
        async reconnectProvider(provider) {
            console.log('🔍 Reconnecting provider:', provider);
            console.log('🔍 Provider type:', typeof provider);
            console.log('🔍 Provider value:', JSON.stringify(provider));
            this.isReconnecting[provider] = true;
            
            const requestData = { provider: provider };
            console.log('🔍 Request data:', JSON.stringify(requestData));
            
            try {
                const response = await fetch(`{{ $isAdmin ? route('admin.cloud-storage.reconnect') : route('employee.cloud-storage.reconnect', ['username' => $user->username]) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                    },
                    body: JSON.stringify(requestData)
                });
                
                console.log('🔍 Response status:', response.status);
                console.log('🔍 Response ok:', response.ok);
                
                const data = await response.json();
                console.log('🔍 Response data:', data);
                
                if (response.ok) {
                    if (data.redirect_url) {
                        // Redirect to OAuth flow
                        window.location.href = data.redirect_url;
                    } else {
                        console.log('🔍 Provider reconnected successfully');
                        this.showSuccess('Provider reconnected successfully');
                        await this.refreshStatus(true);
                    }
                } else {
                    throw new Error(data.message || 'Failed to reconnect provider');
                }
            } catch (error) {
                console.error('🔍 Failed to reconnect provider:', error);
                this.showError(error.message || 'Failed to reconnect provider');
            } finally {
                this.isReconnecting[provider] = false;
            }
        },
        
        async retryFailedUploads(provider) {
            console.log('🔍 Retrying failed uploads for provider:', provider);
            this.isRetrying[provider] = true;
            
            try {
                const response = await fetch('{{ $isAdmin ? route('admin.files.retry-failed') : route('employee.files.retry-failed', ['username' => $user->username]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                    },
                    body: JSON.stringify({ provider: provider })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    console.log('🔍 Failed uploads retry initiated');
                    this.showSuccess(data.message || 'Failed uploads have been queued for retry');
                    await this.refreshStatus(true);
                } else {
                    throw new Error(data.message || 'Failed to retry uploads');
                }
            } catch (error) {
                console.error('🔍 Failed to retry uploads:', error);
                this.showError(error.message || 'Failed to retry uploads');
            } finally {
                this.isRetrying[provider] = false;
            }
        },
        
        async testConnection(provider) {
            console.log('🔍 Testing connection for provider:', provider);
            this.isTesting[provider] = true;
            
            try {
                const response = await fetch('{{ $isAdmin ? route('admin.cloud-storage.test') : route('employee.cloud-storage.test', ['username' => $user->username]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                    },
                    body: JSON.stringify({ provider: provider })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    console.log('🔍 Connection test completed:', data);
                    if (data.success) {
                        this.showSuccess(data.message || 'Connection test completed successfully');
                    } else {
                        this.showError(data.message || 'Connection test failed');
                    }
                    await this.refreshStatus(true);
                } else {
                    throw new Error(data.message || 'Connection test failed');
                }
            } catch (error) {
                console.error('🔍 Connection test failed:', error);
                this.showError(error.message || 'Connection test failed');
            } finally {
                this.isTesting[provider] = false;
            }
        },
        
        getProviderDisplayName(provider) {
            const names = {
                'google-drive': 'Google Drive',
                'onedrive': 'OneDrive'
            };
            return names[provider] || provider;
        },
        
        getConsolidatedStatusMessage(consolidatedStatus) {
            const messages = {
                'healthy': 'Connection is working properly',
                'authentication_required': 'Please reconnect your account',
                'connection_issues': 'Experiencing connectivity problems',
                'not_connected': 'Account not connected'
            };
            return messages[consolidatedStatus] || 'Status unknown';
        },
        
        getStatusIndicatorClass(status) {
            const classes = {
                'healthy': 'bg-green-500',
                'degraded': 'bg-yellow-500',
                'unhealthy': 'bg-red-500',
                'disconnected': 'bg-gray-400',
                // Consolidated status values (prioritized)
                'authentication_required': 'bg-red-500',
                'connection_issues': 'bg-yellow-500',
                'not_connected': 'bg-gray-400'
            };
            return classes[status] || 'bg-gray-400';
        },
        
        getStatusTextClass(status) {
            const classes = {
                'healthy': 'text-green-700',
                'degraded': 'text-yellow-700',
                'unhealthy': 'text-red-700',
                'disconnected': 'text-gray-700',
                // Consolidated status values (prioritized)
                'authentication_required': 'text-red-700',
                'connection_issues': 'text-yellow-700',
                'not_connected': 'text-gray-700'
            };
            return classes[status] || 'text-gray-700';
        },
        
        getStatusDisplayText(status) {
            const texts = {
                'healthy': 'Connected',
                'degraded': 'Degraded',
                'unhealthy': 'Unhealthy',
                'disconnected': 'Disconnected',
                // Consolidated status values (prioritized)
                'authentication_required': 'Authentication Required',
                'connection_issues': 'Connection Issues',
                'not_connected': 'Not Connected'
            };
            return texts[status] || status;
        },
        
        // New enhanced helper methods
        isProviderProcessing(provider) {
            return this.isReconnecting[provider] || this.isRetrying[provider] || this.isTesting[provider];
        },
        
        getConnectionQualityClass(quality) {
            const classes = {
                'excellent': 'bg-green-400',
                'good': 'bg-green-300',
                'fair': 'bg-yellow-400',
                'poor': 'bg-red-400'
            };
            return classes[quality] || 'bg-gray-300';
        },
        
        getConnectionQualityTooltip(quality) {
            const tooltips = {
                'excellent': 'Excellent connection quality',
                'good': 'Good connection quality',
                'fair': 'Fair connection quality - may experience delays',
                'poor': 'Poor connection quality - frequent issues expected'
            };
            return tooltips[quality] || 'Connection quality unknown';
        },
        
        getHealthScoreClass(score) {
            if (score >= 80) return 'bg-green-500';
            if (score >= 60) return 'bg-yellow-500';
            if (score >= 40) return 'bg-orange-500';
            return 'bg-red-500';
        },
        
        getHealthScoreTextClass(score) {
            if (score >= 80) return 'text-green-700';
            if (score >= 60) return 'text-yellow-700';
            if (score >= 40) return 'text-orange-700';
            return 'text-red-700';
        },
        
        getHealthScoreText(score) {
            if (score >= 80) return `Excellent (${score}%)`;
            if (score >= 60) return `Good (${score}%)`;
            if (score >= 40) return `Fair (${score}%)`;
            if (score > 0) return `Poor (${score}%)`;
            return 'Unknown';
        },
        
        getConnectionHealthClass(provider) {
            if (provider.is_healthy) return 'bg-green-500';
            if (provider.consolidated_status === 'connection_issues') return 'bg-yellow-500';
            if (provider.consolidated_status === 'authentication_required') return 'bg-orange-500';
            return 'bg-red-500';
        },
        
        getConnectionHealthTextClass(provider) {
            if (provider.is_healthy) return 'text-green-700';
            if (provider.consolidated_status === 'connection_issues') return 'text-yellow-700';
            if (provider.consolidated_status === 'authentication_required') return 'text-orange-700';
            return 'text-red-700';
        },
        
        getConnectionHealthText(provider) {
            if (provider.is_healthy) return 'Healthy';
            if (provider.consolidated_status === 'connection_issues') return 'Connection Issues';
            if (provider.consolidated_status === 'authentication_required') return 'Authentication Required';
            if (provider.consolidated_status === 'not_connected') return 'Not Connected';
            return 'Unknown';
        },
        
        getTokenStatusIconClass(status) {
            const classes = {
                'valid': 'text-green-500',
                'expired': 'text-red-500',
                'refresh_needed': 'text-yellow-500',
                'invalid': 'text-red-500',
                'missing': 'text-gray-500'
            };
            return classes[status] || 'text-gray-500';
        },
        
        getTokenStatusTextClass(status) {
            const classes = {
                'valid': 'text-green-700',
                'expired': 'text-red-700',
                'refresh_needed': 'text-yellow-700',
                'invalid': 'text-red-700',
                'missing': 'text-gray-700'
            };
            return classes[status] || 'text-gray-700';
        },
        
        getTokenStatusText(status) {
            const texts = {
                'valid': 'Token Valid',
                'expired': 'Token Expired',
                'refresh_needed': 'Token Refresh Needed',
                'invalid': 'Token Invalid',
                'missing': 'No Token'
            };
            return texts[status] || 'Token Status Unknown';
        },
        
        getActionableErrorMessage(provider) {
            // Use enhanced error message service logic
            if (provider.last_error_type) {
                const errorMessages = {
                    'authentication_error': 'Your account needs to be reconnected. Click "Reconnect" to authenticate again.',
                    'token_expired': 'Your access token has expired. The system will attempt to refresh it automatically.',
                    'insufficient_permissions': 'The application needs additional permissions. Please reconnect to grant access.',
                    'quota_exceeded': 'Your storage quota has been exceeded. Free up space or upgrade your plan.',
                    'network_error': 'Unable to connect to the service. Check your internet connection.',
                    'rate_limit_exceeded': 'Too many requests. The system will retry automatically.',
                    'service_unavailable': 'The cloud storage service is temporarily unavailable.',
                    'configuration_error': 'There is a configuration issue. Contact support if this persists.'
                };
                return errorMessages[provider.last_error_type] || provider.last_error_message;
            }
            return provider.last_error_message || 'An unknown error occurred.';
        },
        
        getRecoveryInstructions(provider) {
            if (provider.last_error_type) {
                const instructions = {
                    'authentication_error': 'Click the "Reconnect" button below to re-authenticate your account.',
                    'token_expired': 'Wait for automatic refresh or click "Reconnect" if the issue persists.',
                    'insufficient_permissions': 'Reconnect and ensure you grant all requested permissions.',
                    'quota_exceeded': 'Free up space in your cloud storage or upgrade your plan.',
                    'network_error': 'Check your internet connection and try again.',
                    'rate_limit_exceeded': 'Wait a few minutes before trying again.',
                    'service_unavailable': 'Wait for the service to become available again.',
                    'configuration_error': 'Contact your administrator or support team.'
                };
                return instructions[provider.last_error_type];
            }
            return null;
        },
        
        getRateLimitResetTime(provider) {
            if (provider.rate_limit_reset_at) {
                const resetTime = new Date(provider.rate_limit_reset_at);
                const now = new Date();
                const diffMs = resetTime - now;
                if (diffMs > 0) {
                    const minutes = Math.ceil(diffMs / 60000);
                    return `${minutes} minute${minutes === 1 ? '' : 's'}`;
                }
            }
            return 'soon';
        },
        
        getFailureTrendClass(trend) {
            const classes = {
                'improving': 'bg-green-400',
                'stable': 'bg-yellow-400',
                'worsening': 'bg-red-400'
            };
            return classes[trend] || 'bg-gray-400';
        },
        
        getFailureTrendText(trend) {
            const texts = {
                'improving': 'Improving',
                'stable': 'Stable',
                'worsening': 'Worsening'
            };
            return texts[trend] || 'Unknown';
        },
        
        formatTimestamp(timestamp) {
            if (!timestamp) return 'Never';
            const date = new Date(timestamp);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins} minute${diffMins === 1 ? '' : 's'} ago`;
            
            const diffHours = Math.floor(diffMins / 60);
            if (diffHours < 24) return `${diffHours} hour${diffHours === 1 ? '' : 's'} ago`;
            
            const diffDays = Math.floor(diffHours / 24);
            if (diffDays < 7) return `${diffDays} day${diffDays === 1 ? '' : 's'} ago`;
            
            return date.toLocaleDateString();
        },
        
        getPendingCount(provider) {
            return this.pendingUploads[provider] || 0;
        },
        
        getFailedCount(provider) {
            return this.failedUploads[provider] || 0;
        },
        
        getOverallStatusMessage() {
            const healthyCount = this.providers.filter(p => 
                (p.consolidated_status && p.consolidated_status === 'healthy') || 
                (!p.consolidated_status && p.is_healthy)
            ).length;
            const totalCount = this.providers.length;
            
            if (healthyCount === totalCount) {
                return 'All cloud storage providers are healthy';
            } else if (healthyCount === 0) {
                return 'All cloud storage providers need attention';
            } else {
                return `${healthyCount} of ${totalCount} providers are healthy`;
            }
        },
        
        getOverallStatusIndicatorClass() {
            const healthyCount = this.providers.filter(p => 
                (p.consolidated_status && p.consolidated_status === 'healthy') || 
                (!p.consolidated_status && p.is_healthy)
            ).length;
            const totalCount = this.providers.length;
            
            if (healthyCount === totalCount) {
                return 'bg-green-500';
            } else if (healthyCount === 0) {
                return 'bg-red-500';
            } else {
                return 'bg-yellow-500';
            }
        },
        
        getOverallStatusTextClass() {
            const healthyCount = this.providers.filter(p => 
                (p.consolidated_status && p.consolidated_status === 'healthy') || 
                (!p.consolidated_status && p.is_healthy)
            ).length;
            const totalCount = this.providers.length;
            
            if (healthyCount === totalCount) {
                return 'text-green-700';
            } else if (healthyCount === 0) {
                return 'text-red-700';
            } else {
                return 'text-yellow-700';
            }
        },
        
        getOverallStatusText() {
            const healthyCount = this.providers.filter(p => 
                (p.consolidated_status && p.consolidated_status === 'healthy') || 
                (!p.consolidated_status && p.is_healthy)
            ).length;
            const totalCount = this.providers.length;
            
            if (healthyCount === totalCount) {
                return 'All Healthy';
            } else if (healthyCount === 0) {
                return 'Needs Attention';
            } else {
                return 'Partially Healthy';
            }
        },
        
        // New event handlers for enhanced real-time updates
        handleVisibilityChange(event) {
            if (!document.hidden && this.isOnline) {
                // Page became visible, refresh if stale
                if (Date.now() - this.lastRefreshTime > 30000) {
                    console.log('🔍 Page visible, refreshing stale status');
                    this.refreshStatus(true);
                }
            }
        },
        
        handleWindowFocus() {
            if (this.isOnline && Date.now() - this.lastRefreshTime > 15000) {
                console.log('🔍 Window focused, refreshing status');
                this.refreshStatus(true);
            }
        },
        
        handleOnlineStatus(isOnline) {
            console.log('🔍 Online status changed:', isOnline);
            this.isOnline = isOnline;
            
            if (isOnline) {
                // Came back online, refresh immediately
                this.refreshStatus(true);
                // Restart periodic refresh if it was stopped
                if (!this.refreshInterval) {
                    this.startPeriodicRefresh();
                }
            } else {
                // Went offline, stop periodic refresh
                if (this.refreshInterval) {
                    clearTimeout(this.refreshInterval);
                    this.refreshInterval = null;
                }
            }
        },
        
        showSuccess(message) {
            // Enhanced success notification with better UX
            console.log('✅ Success:', message);
            
            // Create a temporary success indicator
            const successElement = document.createElement('div');
            successElement.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50 transition-opacity duration-300';
            successElement.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    ${message}
                </div>
            `;
            
            document.body.appendChild(successElement);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                successElement.style.opacity = '0';
                setTimeout(() => {
                    if (successElement.parentNode) {
                        successElement.parentNode.removeChild(successElement);
                    }
                }, 300);
            }, 3000);
        },
        
        showError(message) {
            // Enhanced error notification with better UX
            console.error('❌ Error:', message);
            
            // Create a temporary error indicator
            const errorElement = document.createElement('div');
            errorElement.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50 transition-opacity duration-300';
            errorElement.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    ${message}
                </div>
            `;
            
            document.body.appendChild(errorElement);
            
            // Auto-remove after 5 seconds (longer for errors)
            setTimeout(() => {
                errorElement.style.opacity = '0';
                setTimeout(() => {
                    if (errorElement.parentNode) {
                        errorElement.parentNode.removeChild(errorElement);
                    }
                }, 300);
            }, 5000);
        },
        
        // Cleanup method for component destruction
        destroy() {
            console.log('🔍 Cleaning up Cloud Storage Status Widget');
            if (this.refreshInterval) {
                clearTimeout(this.refreshInterval);
                this.refreshInterval = null;
            }
        }
    };
}
</script>