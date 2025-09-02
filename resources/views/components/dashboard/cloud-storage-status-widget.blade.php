@props(['user', 'isAdmin' => false])

@php
    use App\Services\CloudStorageHealthService;
    use App\Models\FileUpload;
    
    $healthService = app(CloudStorageHealthService::class);
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
     x-init="initializeWidget()">
    
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
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
                    
                    <!-- Status Indicator -->
                    <div class="flex items-center">
                        <div class="flex items-center mr-2">
                            <div :class="getStatusIndicatorClass(provider.consolidated_status || provider.status)" class="w-3 h-3 rounded-full mr-2"></div>
                            <span class="text-sm font-medium" :class="getStatusTextClass(provider.consolidated_status || provider.status)" x-text="getStatusDisplayText(provider.consolidated_status || provider.status)"></span>
                        </div>
                    </div>
                </div>

                <!-- Connection Details -->
                <div class="space-y-3">
                    <!-- Last Successful Operation -->
                    <div x-show="provider.last_successful_operation" class="flex items-center text-sm text-gray-600">
                        <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Last success: <span x-text="provider.last_successful_operation"></span></span>
                    </div>



                    <!-- Error Information -->
                    <div x-show="provider.last_error_message" class="flex items-start text-sm text-red-600">
                        <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <div class="font-medium">Last Error:</div>
                            <div x-text="provider.last_error_message" class="break-words"></div>
                        </div>
                    </div>

                    <!-- Consecutive Failures -->
                    <div x-show="provider.consecutive_failures > 0" class="flex items-center text-sm text-orange-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span x-text="`${provider.consecutive_failures} consecutive failure${provider.consecutive_failures === 1 ? '' : 's'}`"></span>
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
        pendingUploads: @json($pendingUploads->map(fn($uploads) => $uploads->count())->toArray()),
        failedUploads: @json($failedUploads->map(fn($uploads) => $uploads->count())->toArray()),
        
        initializeWidget() {
            console.log('🔍 Cloud Storage Status Widget initialized');
            this.initializeLoadingStates();
            this.startPeriodicRefresh();
        },
        
        initializeLoadingStates() {
            this.providers.forEach(provider => {
                this.isReconnecting[provider.provider] = false;
                this.isRetrying[provider.provider] = false;
                this.isTesting[provider.provider] = false;
            });
        },
        
        startPeriodicRefresh() {
            // Refresh status every 30 seconds
            setInterval(() => {
                if (!this.isRefreshing) {
                    this.refreshStatus(true); // Silent refresh
                }
            }, 30000);
        },
        
        async refreshStatus(silent = false) {
            if (!silent) {
                console.log('🔍 Refreshing cloud storage status');
                this.isRefreshing = true;
            }
            
            try {
                const response = await fetch('{{ $isAdmin ? route('admin.cloud-storage.status') : route('employee.cloud-storage.status', ['username' => $user->username]) }}', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.providers = data.providers;
                    this.pendingUploads = data.pending_uploads || {};
                    this.failedUploads = data.failed_uploads || {};
                    
                    if (!silent) {
                        console.log('🔍 Status refreshed successfully');
                    }
                } else {
                    throw new Error('Failed to refresh status');
                }
            } catch (error) {
                console.error('🔍 Failed to refresh status:', error);
                if (!silent) {
                    this.showError('Failed to refresh cloud storage status');
                }
            } finally {
                if (!silent) {
                    this.isRefreshing = false;
                }
            }
        },
        
        async reconnectProvider(provider) {
            console.log('🔍 Reconnecting provider:', provider);
            this.isReconnecting[provider] = true;
            
            try {
                const response = await fetch(`{{ $isAdmin ? route('admin.cloud-storage.reconnect') : route('employee.cloud-storage.reconnect', ['username' => $user->username]) }}`, {
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
                'dropbox': 'Dropbox',
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
                'healthy': 'Healthy',
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
        
        showSuccess(message) {
            // This would integrate with your notification system
            console.log('✅ Success:', message);
            // You can implement toast notifications here
        },
        
        showError(message) {
            // This would integrate with your notification system
            console.error('❌ Error:', message);
            // You can implement toast notifications here
        }
    };
}
</script>