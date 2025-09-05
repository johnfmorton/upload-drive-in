@props(['tokenStatus', 'provider'])

@if($tokenStatus && $tokenStatus['exists'])
<div class="mt-4 p-4 bg-gray-50 rounded-lg border" 
     x-data="tokenStatusWidget({{ json_encode($tokenStatus) }}, '{{ $provider }}')"
     x-init="initializeWidget()">
    
    <!-- Token Status Header -->
    <div class="flex items-center justify-between mb-4">
        <h4 class="text-sm font-medium text-gray-900 flex items-center">
            <svg class="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z"></path>
            </svg>
            Token Information
        </h4>
        
        <!-- Token Health Indicator with Status -->
        <div class="flex items-center space-x-2">
            <div class="flex items-center">
                <div :class="getHealthIndicatorClass()" class="w-3 h-3 rounded-full mr-2 flex-shrink-0"></div>
                <span class="text-xs font-medium" :class="getHealthTextClass()" x-text="tokenStatus.message"></span>
            </div>
            
            <!-- Manual Refresh Button -->
            <button x-show="tokenStatus.can_manually_refresh && !isRefreshing" 
                    @click="manualRefresh()"
                    :disabled="isRefreshing"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Manually refresh token">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
            
            <!-- Refresh Loading State -->
            <div x-show="isRefreshing" class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded">
                <svg class="animate-spin w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Refreshing...
            </div>
        </div>
    </div>

    <!-- Token Lifecycle Information Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-sm mb-4">
        
        <!-- Token Issued Information -->
        <div class="bg-white p-3 rounded border">
            <div class="flex items-center mb-2">
                <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium text-gray-900">Token Issued</span>
            </div>
            <div class="space-y-1">
                <div class="font-medium text-gray-900" x-text="tokenStatus.issued_at_human"></div>
                <div class="text-xs text-gray-500" x-text="tokenStatus.issued_ago_human"></div>
            </div>
        </div>

        <!-- Token Expiration Information -->
        <div class="bg-white p-3 rounded border">
            <div class="flex items-center mb-2">
                <svg class="w-4 h-4 mr-2" :class="getExpirationIconClass()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium text-gray-900">Token Expires</span>
            </div>
            <div class="space-y-1">
                <div class="font-medium" :class="getExpirationTextClass()" x-text="tokenStatus.expires_at_human || 'Never'"></div>
                <div x-show="tokenStatus.expires_in_human" 
                     class="text-xs" 
                     :class="getExpirationSubtextClass()"
                     x-text="tokenStatus.expires_in_human ? `${tokenStatus.expires_in_human} remaining` : ''"></div>
            </div>
        </div>

        <!-- Auto-Renewal Information -->
        <div x-show="tokenStatus.next_renewal_at_human" class="bg-white p-3 rounded border">
            <div class="flex items-center mb-2">
                <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span class="font-medium text-gray-900">Auto-renewal Scheduled</span>
            </div>
            <div class="space-y-1">
                <div class="font-medium text-blue-600" x-text="tokenStatus.next_renewal_at_human"></div>
                <div class="text-xs text-gray-500">Automatic renewal</div>
            </div>
        </div>

        <!-- Last Refresh Information -->
        <div x-show="tokenStatus.last_successful_refresh_human || tokenStatus.refresh_failure_count > 0" class="bg-white p-3 rounded border">
            <div class="flex items-center mb-2">
                <svg class="w-4 h-4 mr-2" :class="getRefreshIconClass()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium text-gray-900">Last Refresh</span>
            </div>
            <div class="space-y-1">
                <div x-show="tokenStatus.last_successful_refresh_human" 
                     class="font-medium text-green-600" 
                     x-text="tokenStatus.last_successful_refresh_human"></div>
                <div x-show="!tokenStatus.last_successful_refresh_human && tokenStatus.refresh_failure_count > 0" 
                     class="font-medium text-red-600">Never successful</div>
                <div x-show="tokenStatus.refresh_failure_count > 0" 
                     class="text-xs text-orange-600"
                     x-text="`${tokenStatus.refresh_failure_count} failure(s)`"></div>
            </div>
        </div>
    </div>

    <!-- Real-time Countdown Timer for Expiring Tokens -->
    <div x-show="shouldShowCountdown()" class="mb-4 p-3 bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-lg">
        <div class="flex items-center justify-between text-sm mb-2">
            <span class="text-yellow-800 font-medium flex items-center">
                <svg class="w-4 h-4 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Token expires in:
            </span>
            <div class="font-mono text-lg font-bold text-yellow-900" x-text="countdownText"></div>
        </div>
        <div class="bg-yellow-200 rounded-full h-2">
            <div class="bg-gradient-to-r from-yellow-500 to-orange-500 h-2 rounded-full transition-all duration-1000" 
                 :style="`width: ${countdownPercentage}%`"></div>
        </div>
        <div class="text-xs text-yellow-700 mt-1">Auto-renewal will begin 15 minutes before expiration</div>
    </div>

    <!-- Token Status Alerts -->
    <div x-show="shouldShowAlert()" class="mb-4 p-3 rounded-lg" :class="getAlertClass()">
        <div class="flex items-start">
            <svg :class="getAlertIconClass()" class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="flex-1">
                <div class="font-medium text-sm" x-text="getAlertTitle()"></div>
                <div class="text-sm mt-1" x-text="getAlertMessage()"></div>
                <div x-show="tokenStatus.last_error && tokenStatus.last_error.message" class="text-xs mt-2 p-2 bg-black bg-opacity-5 rounded">
                    <strong>Last Error:</strong> <span x-text="tokenStatus.last_error.message"></span>
                    <div x-show="tokenStatus.last_error.occurred_at" class="text-gray-500 mt-1" x-text="`Occurred: ${new Date(tokenStatus.last_error.occurred_at).toLocaleString()}`"></div>
                </div>
                <div x-show="getAlertAction()" class="mt-3">
                    <button @click="handleAlertAction()" 
                            class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-red-600 rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                        <span x-text="getAlertAction()"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div x-show="successMessage" x-transition class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
        <div class="flex items-center">
            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-sm text-green-800" x-text="successMessage"></span>
        </div>
    </div>

    <div x-show="errorMessage" x-transition class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-center">
            <svg class="w-4 h-4 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-sm text-red-800" x-text="errorMessage"></span>
        </div>
    </div>

    <!-- Token Scopes Information (Collapsible) -->
    <div x-show="tokenStatus.scopes && tokenStatus.scopes.length > 0" class="mt-4">
        <button @click="showScopes = !showScopes" 
                class="flex items-center text-sm text-gray-600 hover:text-gray-800 font-medium">
            <svg :class="showScopes ? 'rotate-90' : ''" class="w-4 h-4 mr-2 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            Token Permissions & Scopes
        </button>
        <div x-show="showScopes" x-transition class="mt-3 p-3 bg-white rounded border">
            <div class="text-xs text-gray-600 mb-2">This token has access to the following Google Drive permissions:</div>
            <template x-for="scope in tokenStatus.scopes" :key="scope">
                <div class="inline-block bg-blue-50 text-blue-800 rounded-full px-3 py-1 text-xs font-medium mr-2 mb-2" x-text="scope"></div>
            </template>
        </div>
    </div>
</div>

<script>
function tokenStatusWidget(initialTokenStatus, provider) {
    return {
        tokenStatus: initialTokenStatus,
        provider: provider,
        showScopes: false,
        countdownText: '',
        countdownPercentage: 100,
        countdownInterval: null,
        isRefreshing: false,
        successMessage: '',
        errorMessage: '',
        
        initializeWidget() {
            console.log('🔍 Token Status Widget initialized', this.tokenStatus);
            this.startCountdown();
            this.startStatusUpdates();
        },
        
        // Health indicator styling
        getHealthIndicatorClass() {
            return {
                'bg-green-500 shadow-green-200 shadow-sm': this.tokenStatus.health_indicator === 'green',
                'bg-yellow-500 shadow-yellow-200 shadow-sm': this.tokenStatus.health_indicator === 'yellow',
                'bg-red-500 shadow-red-200 shadow-sm': this.tokenStatus.health_indicator === 'red',
                'bg-gray-400': !this.tokenStatus.health_indicator
            };
        },
        
        getHealthTextClass() {
            return {
                'text-green-700': this.tokenStatus.health_indicator === 'green',
                'text-yellow-700': this.tokenStatus.health_indicator === 'yellow',
                'text-red-700': this.tokenStatus.health_indicator === 'red',
                'text-gray-600': !this.tokenStatus.health_indicator
            };
        },
        
        // Expiration styling
        getExpirationIconClass() {
            if (this.tokenStatus.is_expired) {
                return 'text-red-600';
            } else if (this.tokenStatus.is_expiring_soon) {
                return 'text-yellow-600';
            }
            return 'text-green-600';
        },
        
        getExpirationTextClass() {
            if (this.tokenStatus.is_expired) {
                return 'text-red-600 font-semibold';
            } else if (this.tokenStatus.is_expiring_soon) {
                return 'text-yellow-600 font-semibold';
            }
            return 'text-gray-900';
        },
        
        getExpirationSubtextClass() {
            if (this.tokenStatus.is_expired) {
                return 'text-red-500';
            } else if (this.tokenStatus.is_expiring_soon) {
                return 'text-yellow-600';
            }
            return 'text-gray-500';
        },
        
        // Refresh status styling
        getRefreshIconClass() {
            if (this.tokenStatus.refresh_failure_count > 0) {
                return 'text-orange-600';
            } else if (this.tokenStatus.last_successful_refresh_human) {
                return 'text-green-600';
            }
            return 'text-gray-600';
        },
        
        // Alert styling and logic
        shouldShowAlert() {
            return ['requires_intervention', 'expired_manual', 'expired_refreshable', 'healthy_with_warnings'].includes(this.tokenStatus.status);
        },
        
        getAlertClass() {
            switch (this.tokenStatus.status) {
                case 'requires_intervention':
                case 'expired_manual':
                    return 'bg-red-50 border border-red-200';
                case 'expired_refreshable':
                    return 'bg-yellow-50 border border-yellow-200';
                case 'healthy_with_warnings':
                    return 'bg-orange-50 border border-orange-200';
                default:
                    return 'bg-gray-50 border border-gray-200';
            }
        },
        
        getAlertIconClass() {
            switch (this.tokenStatus.status) {
                case 'requires_intervention':
                case 'expired_manual':
                    return 'text-red-500';
                case 'expired_refreshable':
                    return 'text-yellow-500';
                case 'healthy_with_warnings':
                    return 'text-orange-500';
                default:
                    return 'text-gray-500';
            }
        },
        
        getAlertTitle() {
            switch (this.tokenStatus.status) {
                case 'requires_intervention':
                    return 'Manual Action Required';
                case 'expired_manual':
                    return 'Token Expired';
                case 'expired_refreshable':
                    return 'Token Expired - Auto-Renewal Available';
                case 'healthy_with_warnings':
                    return 'Token Healthy with Warnings';
                default:
                    return 'Token Status';
            }
        },
        
        getAlertMessage() {
            switch (this.tokenStatus.status) {
                case 'requires_intervention':
                    return 'Your token requires manual reconnection due to repeated failures. Please reconnect your account.';
                case 'expired_manual':
                    return 'Your token has expired and cannot be automatically renewed. Please reconnect your account.';
                case 'expired_refreshable':
                    return 'Your token has expired but will be automatically renewed when needed.';
                case 'healthy_with_warnings':
                    return `Token is working but has had ${this.tokenStatus.refresh_failure_count} recent refresh failure(s). Monitor for issues.`;
                default:
                    return this.tokenStatus.message;
            }
        },
        
        getAlertAction() {
            if (this.tokenStatus.status === 'requires_intervention' || this.tokenStatus.status === 'expired_manual') {
                return 'Reconnect Account';
            }
            return null;
        },
        
        handleAlertAction() {
            if (this.getAlertAction() === 'Reconnect Account') {
                // Trigger reconnection - this would be handled by the parent component
                this.$dispatch('reconnect-provider', { provider: this.provider });
            }
        },
        
        // Countdown timer logic
        shouldShowCountdown() {
            return this.tokenStatus.time_until_expiration_seconds && 
                   this.tokenStatus.time_until_expiration_seconds < 3600 && // Less than 1 hour
                   !this.tokenStatus.is_expired;
        },
        
        startCountdown() {
            if (!this.shouldShowCountdown()) {
                return;
            }
            
            this.updateCountdown();
            this.countdownInterval = setInterval(() => {
                this.updateCountdown();
            }, 1000);
        },
        
        updateCountdown() {
            if (!this.tokenStatus.time_until_expiration_seconds || this.tokenStatus.is_expired) {
                if (this.countdownInterval) {
                    clearInterval(this.countdownInterval);
                }
                return;
            }
            
            const validatedAt = this.tokenStatus.validated_at ? new Date(this.tokenStatus.validated_at).getTime() : Date.now();
            const elapsed = Math.floor((Date.now() - validatedAt) / 1000);
            const seconds = Math.max(0, this.tokenStatus.time_until_expiration_seconds - elapsed);
            
            if (seconds <= 0) {
                this.countdownText = 'Expired';
                this.countdownPercentage = 0;
                if (this.countdownInterval) {
                    clearInterval(this.countdownInterval);
                }
                // Trigger status refresh when token expires
                this.refreshTokenStatus();
                return;
            }
            
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            if (hours > 0) {
                this.countdownText = `${hours}h ${minutes}m ${secs}s`;
            } else if (minutes > 0) {
                this.countdownText = `${minutes}m ${secs}s`;
            } else {
                this.countdownText = `${secs}s`;
            }
            
            // Calculate percentage (assuming 1 hour total for the progress bar)
            this.countdownPercentage = Math.min(100, (seconds / 3600) * 100);
        },
        
        // Manual refresh functionality
        async manualRefresh() {
            if (this.isRefreshing) {
                return;
            }
            
            this.isRefreshing = true;
            this.clearMessages();
            
            console.log('🔍 Token Status Widget: Starting manual refresh for provider:', this.provider);
            
            try {
                const response = await fetch('/admin/cloud-storage/refresh-token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        provider: this.provider
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    console.log('🔍 Token Status Widget: Manual refresh successful', data);
                    this.successMessage = data.message || 'Token refreshed successfully';
                    
                    // Update token status with new data
                    if (data.token_status) {
                        this.tokenStatus = data.token_status;
                        this.restartCountdown();
                    }
                    
                    // Clear success message after 5 seconds
                    setTimeout(() => {
                        this.successMessage = '';
                    }, 5000);
                    
                } else {
                    console.error('🔍 Token Status Widget: Manual refresh failed', data);
                    this.errorMessage = data.error || 'Token refresh failed';
                    
                    // Clear error message after 10 seconds
                    setTimeout(() => {
                        this.errorMessage = '';
                    }, 10000);
                }
                
            } catch (error) {
                console.error('🔍 Token Status Widget: Manual refresh error', error);
                this.errorMessage = 'Network error occurred while refreshing token';
                
                setTimeout(() => {
                    this.errorMessage = '';
                }, 10000);
            } finally {
                this.isRefreshing = false;
            }
        },
        
        // Status updates
        startStatusUpdates() {
            // Refresh status every 5 minutes
            setInterval(() => {
                this.refreshTokenStatus();
            }, 300000);
        },
        
        async refreshTokenStatus() {
            try {
                const response = await fetch(`/admin/cloud-storage/status?provider=${this.provider}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const data = await response.json();
                
                if (data.success && data.token_status) {
                    console.log('🔍 Token Status Widget: Status updated', data.token_status);
                    this.tokenStatus = data.token_status;
                    this.restartCountdown();
                }
                
            } catch (error) {
                console.error('🔍 Token Status Widget: Failed to refresh status', error);
            }
        },
        
        restartCountdown() {
            if (this.countdownInterval) {
                clearInterval(this.countdownInterval);
                this.countdownInterval = null;
            }
            this.startCountdown();
        },
        
        clearMessages() {
            this.successMessage = '';
            this.errorMessage = '';
        },
        
        destroy() {
            if (this.countdownInterval) {
                clearInterval(this.countdownInterval);
            }
        }
    };
}
</script>
@else
<div class="mt-4 p-4 bg-gray-50 rounded-lg border">
    <div class="flex items-center text-sm text-gray-600">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>{{ $tokenStatus['message'] ?? 'No token information available' }}</span>
    </div>
</div>
@endif