@props(['user', 'isAdmin' => false, 'storageProvider' => null])

@php
    use App\Services\CloudStorageHealthService;
    use App\Services\CloudStorageErrorMessageService;
    use App\Models\FileUpload;
    
    // Hide widget for system-level storage providers (like Amazon S3)
    // System-level providers don't require user authentication, so there's no connection status to manage
    if ($storageProvider && !$storageProvider['requires_user_auth']) {
        return;
    }
    
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
                            <template x-if="provider.provider === 'amazon-s3'">
                                <svg class="w-8 h-8 text-orange-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M6.33 6.33A7.5 7.5 0 0 1 12 4.5c2.07 0 3.93.84 5.29 2.19l1.42-1.42A9.5 9.5 0 0 0 12 2.5c-2.62 0-5 1.06-6.71 2.79l1.04 1.04zm11.34 11.34A7.5 7.5 0 0 1 12 19.5c-2.07 0-3.93-.84-5.29-2.19l-1.42 1.42A9.5 9.5 0 0 0 12 21.5c2.62 0 5-1.06 6.71-2.79l-1.04-1.04zM12 7.5c-2.48 0-4.5 2.02-4.5 4.5s2.02 4.5 4.5 4.5 4.5-2.02 4.5-4.5-2.02-4.5-4.5-4.5zm0 7.5c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3z"/>
                                </svg>
                            </template>
                            <!-- Add more provider icons as needed -->
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-medium text-gray-900" x-text="getProviderDisplayName(provider.provider)"></h3>
                            <p class="text-xs text-gray-500" x-text="getProviderStatusMessage(provider)"></p>
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

                    <!-- S3 Connection Details (Bucket and Region) -->
                    <div x-show="provider.provider === 'amazon-s3' && provider.provider_specific_data && (provider.provider_specific_data.bucket || provider.provider_specific_data.region)" class="space-y-2">
                        <div x-show="provider.provider_specific_data && provider.provider_specific_data.bucket" class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                            </svg>
                            <span>Bucket: <span class="font-medium" x-text="provider.provider_specific_data.bucket"></span></span>
                        </div>
                        <div x-show="provider.provider_specific_data && provider.provider_specific_data.region" class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Region: <span class="font-medium" x-text="provider.provider_specific_data.region"></span></span>
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
                    <div x-show="shouldShowErrorDetails(provider)" class="p-3 border rounded-md" :class="getErrorDisplayClass(provider)">
                        <div class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" :class="getErrorIconClass(provider)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="flex-1">
                                <div class="font-medium text-sm" :class="getErrorTitleClass(provider)" x-text="getErrorTitle(provider)"></div>
                                <div class="text-sm mt-1" :class="getErrorMessageClass(provider)" x-text="getProviderStatusMessage(provider)"></div>
                                <!-- Rate Limiting Information with Countdown -->
                                <div x-show="isProviderRateLimited(provider)" class="mt-2">
                                    <div class="text-xs font-medium" :class="getErrorMessageClass(provider)">Next retry available:</div>
                                    <div class="text-xs" :class="getErrorMessageClass(provider)" x-text="getRateLimitCountdown(provider)"></div>
                                </div>
                                <!-- Recovery Instructions -->
                                <div x-show="getRecoveryInstructions(provider)" class="mt-2">
                                    <div class="text-xs font-medium" :class="getErrorMessageClass(provider)">Recommended Action:</div>
                                    <div class="text-xs" :class="getErrorMessageClass(provider)" x-text="getRecoveryInstructions(provider)"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rate Limiting Information with Enhanced Display -->
                    <div x-show="isProviderRateLimited(provider)" class="flex items-center justify-between text-sm p-2 bg-yellow-50 border border-yellow-200 rounded-md">
                        <div class="flex items-center text-yellow-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Rate limited</span>
                        </div>
                        <div class="text-yellow-600 text-xs font-medium" x-text="getRateLimitCountdown(provider)"></div>
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
                    <!-- Configure S3 Button (for not connected S3) -->
                    <template x-if="provider.provider === 'amazon-s3' && (provider.consolidated_status === 'not_connected' || !provider.is_healthy)">
                        <a :href="'{{ $isAdmin ? route('admin.cloud-storage.index') : route('employee.cloud-storage.index', ['username' => $user->username]) }}'"
                           class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>{{ __('Configure S3') }}</span>
                        </a>
                    </template>

                    <!-- Reconnect Button (for OAuth providers like Google Drive) -->
                    <template x-if="provider.provider !== 'amazon-s3' && ((provider.consolidated_status && (provider.consolidated_status === 'authentication_required' || provider.consolidated_status === 'not_connected')) || provider.requires_reconnection || provider.is_disconnected)">
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

                    <!-- Test Connection Button with Rate Limiting Protection -->
                    <template x-if="shouldShowTestButton(provider)">
                        <button @click="testConnection(provider.provider)"
                                :disabled="isTesting[provider.provider] || isProviderRateLimited(provider)"
                                :class="getTestButtonClass(provider)"
                                class="inline-flex items-center px-3 py-2 border text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="!isTesting[provider.provider] && !isProviderRateLimited(provider)" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <svg x-show="isTesting[provider.provider]" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg x-show="isProviderRateLimited(provider)" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span x-text="getTestButtonText(provider)"></span>
                        </button>
                    </template>
                </div>

                <!-- Enhanced Token Status Widget -->
                <template x-if="provider.token_status">
                    <x-dashboard.token-status-widget 
                        :token-status="null" 
                        :provider="'provider.provider'"
                        x-data="{ tokenStatus: provider.token_status }"
                        x-on:reconnect-provider.window="reconnectProvider($event.detail.provider)" />
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
        countdownInterval: null,
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
            this.startRateLimitCountdown();
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
            console.log('🔍 Testing connection with rate limiting protection for provider:', provider);
            
            // Check for rate limiting before attempting test
            const providerData = this.providers.find(p => p.provider === provider);
            if (this.isProviderRateLimited(providerData)) {
                const countdown = this.getRateLimitCountdown(providerData);
                this.showError(`Rate limited. Please wait ${countdown} before testing again.`);
                return;
            }
            
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
                    
                    // Update provider data with enhanced information from test
                    const providerIndex = this.providers.findIndex(p => p.provider === provider);
                    if (providerIndex !== -1) {
                        // Update with latest status information from test
                        if (data.token_status) {
                            this.providers[providerIndex].token_status = data.token_status;
                        }
                        
                        // Update rate limiting status if returned
                        if (data.is_rate_limited !== undefined) {
                            this.providers[providerIndex].is_rate_limited = data.is_rate_limited;
                        }
                        
                        if (data.rate_limit_reset_at) {
                            this.providers[providerIndex].rate_limit_reset_at = data.rate_limit_reset_at;
                        }
                        
                        console.log('🔍 Updated provider status for:', provider);
                    }
                    
                    // Show appropriate message based on test result
                    if (data.success) {
                        let message = data.message || 'Connection test completed successfully';
                        if (data.validation_details && data.validation_details.validation_time_ms) {
                            message += ` (${data.validation_details.validation_time_ms}ms)`;
                        }
                        this.showSuccess(message);
                    } else {
                        // Handle rate limiting in test response
                        if (data.is_rate_limited || (data.message && data.message.toLowerCase().includes('rate limit'))) {
                            const retryTime = data.retry_after ? `${Math.ceil(data.retry_after / 60)} minutes` : 'a few minutes';
                            this.showError(`Rate limited. Please wait ${retryTime} before testing again.`);
                            
                            // Update provider rate limiting status
                            if (providerIndex !== -1) {
                                this.providers[providerIndex].is_rate_limited = true;
                                if (data.rate_limit_reset_at) {
                                    this.providers[providerIndex].rate_limit_reset_at = data.rate_limit_reset_at;
                                }
                            }
                        } else {
                            let errorMessage = data.message || 'Connection test failed';
                            if (data.error_type_localized) {
                                errorMessage += ` (${data.error_type_localized})`;
                            }
                            this.showError(errorMessage);
                        }
                    }
                    
                    // Refresh status to get latest information
                    await this.refreshStatus(true);
                } else {
                    // Handle HTTP error responses
                    if (response.status === 429) {
                        this.showError('Rate limited. Please wait before testing again.');
                        
                        // Mark provider as rate limited
                        const providerIndex = this.providers.findIndex(p => p.provider === provider);
                        if (providerIndex !== -1) {
                            this.providers[providerIndex].is_rate_limited = true;
                        }
                    } else {
                        throw new Error(data.message || 'Connection test failed');
                    }
                }
            } catch (error) {
                console.error('🔍 Connection test failed:', error);
                
                // Handle network errors that might indicate rate limiting
                if (error.message.includes('429') || error.message.toLowerCase().includes('rate limit')) {
                    this.showError('Rate limited. Please wait before testing again.');
                } else {
                    this.showError(error.message || 'Connection test failed');
                }
            } finally {
                this.isTesting[provider] = false;
            }
        },
        
        getProviderDisplayName(provider) {
            const names = {
                'google-drive': 'Google Drive',
                'amazon-s3': 'Amazon S3',
                'onedrive': 'OneDrive'
            };
            return names[provider] || provider;
        },
        
        getProviderStatusMessage(provider) {
            // Use single message source from backend - prioritize status_message from backend
            if (provider.status_message && provider.status_message.trim()) {
                return provider.status_message;
            }
            
            // Fallback to consolidated status message if no specific message
            return this.getConsolidatedStatusMessage(provider.consolidated_status || provider.status);
        },
        
        getConsolidatedStatusMessage(consolidatedStatus) {
            const messages = {
                'healthy': 'Connected and working properly',
                'authentication_required': 'Authentication required. Please reconnect your account.',
                'connection_issues': 'Connection issue detected. Please test your connection.',
                'not_connected': 'Account not connected. Please set up your cloud storage connection.'
            };
            return messages[consolidatedStatus] || 'Status unknown. Please refresh or contact support.';
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
        
        shouldShowErrorDetails(provider) {
            // Only show error details if there's an actual issue and we're not in a healthy state
            const consolidatedStatus = provider.consolidated_status || provider.status;
            
            // Don't show error details for healthy connections
            if (consolidatedStatus === 'healthy') {
                return false;
            }
            
            // Show error details for problematic states
            return ['authentication_required', 'connection_issues', 'not_connected'].includes(consolidatedStatus) ||
                   provider.last_error_message ||
                   this.isProviderRateLimited(provider);
        },
        
        getErrorDisplayClass(provider) {
            if (this.isProviderRateLimited(provider)) {
                return 'bg-yellow-50 border-yellow-200';
            }
            
            const consolidatedStatus = provider.consolidated_status || provider.status;
            if (consolidatedStatus === 'authentication_required') {
                return 'bg-orange-50 border-orange-200';
            }
            
            return 'bg-red-50 border-red-200';
        },
        
        getErrorIconClass(provider) {
            if (this.isProviderRateLimited(provider)) {
                return 'text-yellow-500';
            }
            
            const consolidatedStatus = provider.consolidated_status || provider.status;
            if (consolidatedStatus === 'authentication_required') {
                return 'text-orange-500';
            }
            
            return 'text-red-500';
        },
        
        getErrorTitle(provider) {
            if (this.isProviderRateLimited(provider)) {
                return 'Rate Limited';
            }
            
            const consolidatedStatus = provider.consolidated_status || provider.status;
            if (consolidatedStatus === 'authentication_required') {
                return 'Authentication Required';
            }
            
            if (consolidatedStatus === 'not_connected') {
                return 'Not Connected';
            }
            
            return 'Connection Issue';
        },
        
        getErrorTitleClass(provider) {
            if (this.isProviderRateLimited(provider)) {
                return 'text-yellow-800';
            }
            
            const consolidatedStatus = provider.consolidated_status || provider.status;
            if (consolidatedStatus === 'authentication_required') {
                return 'text-orange-800';
            }
            
            return 'text-red-800';
        },
        
        getErrorMessageClass(provider) {
            if (this.isProviderRateLimited(provider)) {
                return 'text-yellow-700';
            }
            
            const consolidatedStatus = provider.consolidated_status || provider.status;
            if (consolidatedStatus === 'authentication_required') {
                return 'text-orange-700';
            }
            
            return 'text-red-700';
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
        
        isProviderRateLimited(provider) {
            // Check multiple indicators for rate limiting
            return provider.is_rate_limited || 
                   provider.last_error_type === 'token_refresh_rate_limited' ||
                   (provider.status_message && provider.status_message.toLowerCase().includes('too many')) ||
                   (provider.last_error_message && provider.last_error_message.toLowerCase().includes('rate limit'));
        },
        
        getRateLimitCountdown(provider) {
            // Try to get countdown from various sources
            if (provider.rate_limit_reset_at) {
                const resetTime = new Date(provider.rate_limit_reset_at);
                const now = new Date();
                const diffMs = resetTime - now;
                if (diffMs > 0) {
                    const minutes = Math.ceil(diffMs / 60000);
                    const seconds = Math.ceil(diffMs / 1000);
                    
                    if (seconds < 60) {
                        return `${seconds} second${seconds === 1 ? '' : 's'}`;
                    } else {
                        return `${minutes} minute${minutes === 1 ? '' : 's'}`;
                    }
                }
            }
            
            // Fallback to extracting time from status message
            const message = provider.status_message || provider.last_error_message || '';
            const timeMatch = message.match(/(\d+)\s+(minute|second)s?/i);
            if (timeMatch) {
                return `${timeMatch[1]} ${timeMatch[2]}${parseInt(timeMatch[1]) === 1 ? '' : 's'}`;
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
        
        shouldShowTestButton(provider) {
            const consolidatedStatus = provider.consolidated_status || provider.status;
            
            // Show test button for healthy and connection issues states, but not for disconnected/auth required
            return ['healthy', 'connection_issues'].includes(consolidatedStatus) || 
                   provider.is_healthy || 
                   provider.is_degraded;
        },
        
        getTestButtonClass(provider) {
            if (this.isProviderRateLimited(provider)) {
                return 'border-yellow-300 text-yellow-700 bg-yellow-50 cursor-not-allowed';
            }
            
            return 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50';
        },
        
        getTestButtonText(provider) {
            if (this.isTesting[provider.provider]) {
                return '{{ __('Testing...') }}';
            }
            
            if (this.isProviderRateLimited(provider)) {
                const countdown = this.getRateLimitCountdown(provider);
                return `{{ __('Rate Limited') }} (${countdown})`;
            }
            
            return '{{ __('Test Connection') }}';
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
        
        startRateLimitCountdown() {
            // Update rate limit countdowns every second
            this.countdownInterval = setInterval(() => {
                this.providers.forEach(provider => {
                    if (this.isProviderRateLimited(provider) && provider.rate_limit_reset_at) {
                        const resetTime = new Date(provider.rate_limit_reset_at);
                        const now = new Date();
                        const diffMs = resetTime - now;
                        
                        // If rate limit has expired, mark as no longer rate limited
                        if (diffMs <= 0) {
                            provider.is_rate_limited = false;
                            provider.rate_limit_reset_at = null;
                            console.log('🔍 Rate limit expired for provider:', provider.provider);
                        }
                    }
                });
            }, 1000);
        },
        
        // Cleanup method for component destruction
        destroy() {
            console.log('🔍 Cleaning up Cloud Storage Status Widget');
            if (this.refreshInterval) {
                clearTimeout(this.refreshInterval);
                this.refreshInterval = null;
            }
            if (this.countdownInterval) {
                clearInterval(this.countdownInterval);
                this.countdownInterval = null;
            }
        }
    };
}
</script>