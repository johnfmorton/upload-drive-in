@extends('layouts.admin')

@section('title', 'Token Refresh Configuration')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="tokenRefreshConfig()">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Token Refresh Configuration</h1>
        <p class="mt-2 text-gray-600">Manage token refresh settings and feature flags for gradual rollout.</p>
    </div>

    <!-- Configuration Status -->
    <div class="mb-6">
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-medium text-gray-900">Configuration Status</h2>
                <div class="flex space-x-2">
                    <button @click="refreshStatus()" 
                            :disabled="loading"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Refresh
                    </button>
                    @if($allowRuntimeChanges)
                    <button @click="clearCache()" 
                            :disabled="loading"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Clear Cache
                    </button>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm font-medium text-gray-500">Environment</div>
                    <div class="mt-1 text-lg font-semibold text-gray-900">{{ $configuration['environment'] }}</div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm font-medium text-gray-500">Runtime Changes</div>
                    <div class="mt-1 text-lg font-semibold {{ $allowRuntimeChanges ? 'text-green-600' : 'text-red-600' }}">
                        {{ $allowRuntimeChanges ? 'Enabled' : 'Disabled' }}
                    </div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm font-medium text-gray-500">Validation Status</div>
                    <div class="mt-1 text-lg font-semibold {{ empty($validationErrors) ? 'text-green-600' : 'text-red-600' }}">
                        {{ empty($validationErrors) ? 'Valid' : 'Issues Found' }}
                    </div>
                </div>
            </div>

            @if(!empty($validationErrors))
            <div class="mt-4 bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Configuration Issues</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach($validationErrors as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Feature Flags -->
    <div class="mb-6">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Feature Flags</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($configuration['features'] as $feature => $enabled)
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div>
                        <div class="font-medium text-gray-900">{{ ucwords(str_replace('_', ' ', $feature)) }}</div>
                        <div class="text-sm text-gray-500">
                            @switch($feature)
                                @case('proactive_refresh')
                                    Automatically refresh tokens before expiration
                                    @break
                                @case('live_validation')
                                    Perform real-time API validation
                                    @break
                                @case('automatic_recovery')
                                    Automatically recover from connection issues
                                    @break
                                @case('background_maintenance')
                                    Run background maintenance jobs
                                    @break
                                @case('health_monitoring')
                                    Monitor connection health status
                                    @break
                                @case('enhanced_dashboard')
                                    Show enhanced dashboard features
                                    @break
                                @case('enhanced_logging')
                                    Enable comprehensive logging
                                    @break
                                @default
                                    {{ $feature }}
                            @endswitch
                        </div>
                    </div>
                    @if($allowRuntimeChanges && in_array("features.{$feature}", $modifiableSettings))
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               :checked="configuration.features.{{ $feature }}"
                               @change="toggleFeature('{{ $feature }}', $event.target.checked)"
                               :disabled="loading"
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                    @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $enabled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $enabled ? 'Enabled' : 'Disabled' }}
                    </span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Timing Configuration -->
    <div class="mb-6">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Timing Configuration</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($configuration['timing'] as $key => $value)
                <div class="p-4 border border-gray-200 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ ucwords(str_replace('_', ' ', $key)) }}
                    </label>
                    @if($allowRuntimeChanges && in_array("timing.{$key}", $modifiableSettings))
                    <input type="number" 
                           x-model="configuration.timing.{{ $key }}"
                           @change="updateSetting('timing.{{ $key }}', $event.target.value)"
                           :disabled="loading"
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    @else
                    <div class="text-lg font-semibold text-gray-900">{{ $value }}</div>
                    @endif
                    <div class="mt-1 text-xs text-gray-500">
                        @switch($key)
                            @case('proactive_refresh_minutes')
                                Minutes before expiration to refresh
                                @break
                            @case('background_refresh_minutes')
                                Minutes before expiration for background refresh
                                @break
                            @case('max_retry_attempts')
                                Maximum retry attempts for failed operations
                                @break
                            @case('retry_base_delay_seconds')
                                Base delay for exponential backoff (seconds)
                                @break
                            @case('coordination_lock_ttl')
                                Lock TTL for coordination (seconds)
                                @break
                            @case('health_cache_ttl_healthy')
                                Cache TTL for healthy status (seconds)
                                @break
                            @case('health_cache_ttl_error')
                                Cache TTL for error status (seconds)
                                @break
                            @default
                                {{ $key }}
                        @endswitch
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Notification Configuration -->
    <div class="mb-6">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Notification Configuration</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 border border-gray-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-gray-900">Notifications Enabled</div>
                            <div class="text-sm text-gray-500">Send email notifications for token issues</div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $configuration['notifications']['enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $configuration['notifications']['enabled'] ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                </div>
                <div class="p-4 border border-gray-200 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Throttle Hours</label>
                    @if($allowRuntimeChanges && in_array('notifications.throttle_hours', $modifiableSettings))
                    <input type="number" 
                           x-model="configuration.notifications.throttle_hours"
                           @change="updateSetting('notifications.throttle_hours', $event.target.value)"
                           :disabled="loading"
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    @else
                    <div class="text-lg font-semibold text-gray-900">{{ $configuration['notifications']['throttle_hours'] }}</div>
                    @endif
                    <div class="mt-1 text-xs text-gray-500">Hours between notifications of same type</div>
                </div>
                <div class="p-4 border border-gray-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-gray-900">Admin Escalation</div>
                            <div class="text-sm text-gray-500">Escalate to admin when notifications fail</div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $configuration['notifications']['escalate_to_admin'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $configuration['notifications']['escalate_to_admin'] ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                </div>
                <div class="p-4 border border-gray-200 rounded-lg">
                    <div class="font-medium text-gray-900">Max Notification Failures</div>
                    <div class="text-lg font-semibold text-gray-900">{{ $configuration['notifications']['max_notification_failures'] }}</div>
                    <div class="text-xs text-gray-500">Failures before escalation</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rate Limiting Configuration -->
    <div class="mb-6">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Rate Limiting Configuration</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($configuration['rate_limiting'] as $key => $value)
                <div class="p-4 border border-gray-200 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ ucwords(str_replace('_', ' ', $key)) }}
                    </label>
                    @if($allowRuntimeChanges && in_array("rate_limiting.{$key}", $modifiableSettings))
                    <input type="number" 
                           x-model="configuration.rate_limiting.{{ $key }}"
                           @change="updateSetting('rate_limiting.{{ $key }}', $event.target.value)"
                           :disabled="loading"
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    @else
                    <div class="text-lg font-semibold text-gray-900">{{ $value ? 'Yes' : ($value === false ? 'No' : $value) }}</div>
                    @endif
                    <div class="mt-1 text-xs text-gray-500">
                        @switch($key)
                            @case('max_attempts_per_hour')
                                Maximum refresh attempts per user per hour
                                @break
                            @case('max_health_checks_per_minute')
                                Maximum health checks per user per minute
                                @break
                            @case('ip_based_limiting')
                                Enable IP-based rate limiting
                                @break
                            @case('max_requests_per_ip_per_hour')
                                Maximum requests per IP per hour
                                @break
                            @default
                                {{ $key }}
                        @endswitch
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Security Configuration -->
    <div class="mb-6">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Security Configuration</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($configuration['security'] as $key => $value)
                <div class="p-4 border border-gray-200 rounded-lg">
                    <div class="font-medium text-gray-900">{{ ucwords(str_replace('_', ' ', $key)) }}</div>
                    <div class="text-lg font-semibold text-gray-900 mt-1">
                        @if(is_bool($value))
                            {{ $value ? 'Enabled' : 'Disabled' }}
                        @else
                            {{ $value }}
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        @switch($key)
                            @case('token_rotation')
                                Rotate tokens on successful refresh
                                @break
                            @case('audit_logging')
                                Log all token operations for audit
                                @break
                            @case('security_log_level')
                                Log level for security events
                                @break
                            @case('detailed_error_logging')
                                Include detailed error information in logs
                                @break
                            @default
                                {{ $key }}
                        @endswitch
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div x-show="showConfirmationModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showConfirmationModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="closeConfirmationModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showConfirmationModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Confirm Configuration Change
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500" x-text="confirmationMessage"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button @click="confirmAction()" 
                            :disabled="loading"
                            type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                        Confirm
                    </button>
                    <button @click="closeConfirmationModal()" 
                            :disabled="loading"
                            type="button" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function tokenRefreshConfig() {
    return {
        loading: false,
        showConfirmationModal: false,
        confirmationMessage: '',
        pendingAction: null,
        configuration: @json($configuration),

        async toggleFeature(feature, enabled) {
            if (this.loading) return;

            try {
                this.loading = true;
                const response = await fetch('{{ route("admin.token-refresh.toggle-feature") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        feature: feature,
                        enabled: enabled
                    })
                });

                const data = await response.json();

                if (data.requires_confirmation) {
                    this.showConfirmation(data.message, () => this.toggleFeatureConfirmed(feature, enabled));
                    return;
                }

                if (data.success) {
                    this.configuration.features[feature] = enabled;
                    this.showSuccess(data.message);
                } else {
                    this.showError(data.error || 'Failed to toggle feature');
                }
            } catch (error) {
                this.showError('Network error occurred');
            } finally {
                this.loading = false;
            }
        },

        async toggleFeatureConfirmed(feature, enabled) {
            try {
                this.loading = true;
                const response = await fetch('{{ route("admin.token-refresh.toggle-feature") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        feature: feature,
                        enabled: enabled,
                        confirmed: true
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.configuration.features[feature] = enabled;
                    this.showSuccess(data.message);
                } else {
                    this.showError(data.error || 'Failed to toggle feature');
                }
            } catch (error) {
                this.showError('Network error occurred');
            } finally {
                this.loading = false;
                this.closeConfirmationModal();
            }
        },

        async updateSetting(key, value) {
            if (this.loading) return;

            try {
                this.loading = true;
                const response = await fetch('{{ route("admin.token-refresh.update-setting") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        key: key,
                        value: value
                    })
                });

                const data = await response.json();

                if (data.requires_confirmation) {
                    this.showConfirmation(data.message, () => this.updateSettingConfirmed(key, value));
                    return;
                }

                if (data.success) {
                    this.updateConfigurationValue(key, data.new_value);
                    this.showSuccess(data.message);
                } else {
                    this.showError(data.error || 'Failed to update setting');
                }
            } catch (error) {
                this.showError('Network error occurred');
            } finally {
                this.loading = false;
            }
        },

        async updateSettingConfirmed(key, value) {
            try {
                this.loading = true;
                const response = await fetch('{{ route("admin.token-refresh.update-setting") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        key: key,
                        value: value,
                        confirmed: true
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.updateConfigurationValue(key, data.new_value);
                    this.showSuccess(data.message);
                } else {
                    this.showError(data.error || 'Failed to update setting');
                }
            } catch (error) {
                this.showError('Network error occurred');
            } finally {
                this.loading = false;
                this.closeConfirmationModal();
            }
        },

        async clearCache() {
            if (this.loading) return;

            try {
                this.loading = true;
                const response = await fetch('{{ route("admin.token-refresh.clear-cache") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess(data.message);
                } else {
                    this.showError(data.error || 'Failed to clear cache');
                }
            } catch (error) {
                this.showError('Network error occurred');
            } finally {
                this.loading = false;
            }
        },

        async refreshStatus() {
            if (this.loading) return;

            try {
                this.loading = true;
                const response = await fetch('{{ route("admin.token-refresh.status") }}');
                const data = await response.json();

                if (data.configuration) {
                    this.configuration = data.configuration;
                    this.showSuccess('Status refreshed successfully');
                } else {
                    this.showError(data.error || 'Failed to refresh status');
                }
            } catch (error) {
                this.showError('Network error occurred');
            } finally {
                this.loading = false;
            }
        },

        updateConfigurationValue(key, value) {
            const parts = key.split('.');
            let obj = this.configuration;
            for (let i = 0; i < parts.length - 1; i++) {
                obj = obj[parts[i]];
            }
            obj[parts[parts.length - 1]] = value;
        },

        showConfirmation(message, action) {
            this.confirmationMessage = message;
            this.pendingAction = action;
            this.showConfirmationModal = true;
        },

        confirmAction() {
            if (this.pendingAction) {
                this.pendingAction();
                this.pendingAction = null;
            }
        },

        closeConfirmationModal() {
            this.showConfirmationModal = false;
            this.confirmationMessage = '';
            this.pendingAction = null;
        },

        showSuccess(message) {
            // You can implement a toast notification system here
            alert('Success: ' + message);
        },

        showError(message) {
            // You can implement a toast notification system here
            alert('Error: ' + message);
        }
    }
}
</script>
@endsection