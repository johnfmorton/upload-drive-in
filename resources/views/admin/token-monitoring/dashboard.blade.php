@extends('layouts.admin')

@section('title', __('messages.token_monitoring.dashboard_title'))

@section('content')
<div class="container-fluid" x-data="tokenMonitoringDashboard()" x-init="initDashboard()">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">{{ __('messages.token_monitoring.dashboard_title') }}</h1>
                <div class="d-flex gap-2">
                    <select class="form-select" x-model="selectedProvider" @change="refreshDashboard()">
                        <option value="google-drive">{{ __('messages.token_monitoring.google_drive') }}</option>
                    </select>
                    <select class="form-select" x-model="selectedHours" @change="refreshDashboard()">
                        <option value="1">{{ __('messages.token_monitoring.last_hour') }}</option>
                        <option value="6">{{ __('messages.token_monitoring.last_6_hours') }}</option>
                        <option value="24">{{ __('messages.token_monitoring.last_24_hours') }}</option>
                        <option value="168">{{ __('messages.token_monitoring.last_week') }}</option>
                    </select>
                    <button class="btn btn-outline-primary" @click="refreshDashboard()" :disabled="isLoading">
                        <i class="fas fa-sync-alt" :class="{'fa-spin': isLoading}"></i>
                        {{ __('messages.token_monitoring.refresh_dashboard') }}
                    </button>
                    <button class="btn btn-outline-secondary" @click="exportData()">
                        <i class="fas fa-download"></i>
                        {{ __('messages.token_monitoring.export_data') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="isLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">{{ __('messages.token_monitoring.loading') }}</span>
        </div>
        <p class="mt-2">{{ __('messages.token_monitoring.loading_dashboard_data') }}</p>
    </div>

    <!-- Dashboard Content -->
    <div x-show="!isLoading && dashboardData" x-cloak>
        <!-- Overview Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">{{ __('messages.token_monitoring.connected_users') }}</h6>
                                <h3 class="mb-0" x-text="dashboardData?.overview?.connected_users || 0"></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x opacity-75"></i>
                            </div>
                        </div>
                        <small class="opacity-75">
                            <span x-text="dashboardData?.overview?.total_users || 0"></span> {{ __('messages.token_monitoring.total_users_label') }}
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" :class="getSuccessRateCardClass()">
                    <div class="card-body text-white">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">{{ __('messages.token_monitoring.success_rate') }}</h6>
                                <h3 class="mb-0" x-text="formatPercentage(dashboardData?.overview?.success_rate)"></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-check-circle fa-2x opacity-75"></i>
                            </div>
                        </div>
                        <small class="opacity-75">{{ __('messages.token_monitoring.token_refresh_operations') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">{{ __('messages.token_monitoring.average_refresh_time') }}</h6>
                                <h3 class="mb-0" x-text="formatDuration(dashboardData?.overview?.average_refresh_time)"></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x opacity-75"></i>
                            </div>
                        </div>
                        <small class="opacity-75">{{ __('messages.token_monitoring.milliseconds') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" :class="getAlertsCardClass()">
                    <div class="card-body text-white">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">{{ __('messages.token_monitoring.active_alerts') }}</h6>
                                <h3 class="mb-0" x-text="dashboardData?.overview?.active_alerts || 0"></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                            </div>
                        </div>
                        <small class="opacity-75" x-text="dashboardData?.overview?.overall_health || 'Unknown'"></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('messages.token_monitoring.performance_metrics_title') }}</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="performanceTrendsChart" height="100"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('messages.token_monitoring.system_status_title') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>{{ __('messages.token_monitoring.token_refresh') }}</span>
                                <span class="badge" :class="getHealthBadgeClass(dashboardData?.performance_metrics?.system_health?.token_refresh_health)" 
                                      x-text="dashboardData?.performance_metrics?.system_health?.token_refresh_health || 'Unknown'"></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>{{ __('messages.token_monitoring.api_connectivity') }}</span>
                                <span class="badge" :class="getHealthBadgeClass(dashboardData?.performance_metrics?.system_health?.api_connectivity_health)" 
                                      x-text="dashboardData?.performance_metrics?.system_health?.api_connectivity_health || 'Unknown'"></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>{{ __('messages.token_monitoring.cache_performance') }}</span>
                                <span class="badge" :class="getHealthBadgeClass(dashboardData?.performance_metrics?.system_health?.cache_performance_health)" 
                                      x-text="dashboardData?.performance_metrics?.system_health?.cache_performance_health || 'Unknown'"></span>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="h4 mb-1" :class="getOverallHealthClass()" x-text="dashboardData?.performance_metrics?.system_health?.overall_status || 'Unknown'"></div>
                            <small class="text-muted">{{ __('messages.token_monitoring.overall_system_health') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Metrics Row -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('messages.token_monitoring.token_status_title') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="h4 text-success" x-text="dashboardData?.token_status_summary?.valid_tokens || 0"></div>
                                <small class="text-muted">{{ __('messages.token_monitoring.valid') }}</small>
                            </div>
                            <div class="col-4">
                                <div class="h4 text-warning" x-text="dashboardData?.token_status_summary?.expiring_warning || 0"></div>
                                <small class="text-muted">{{ __('messages.token_monitoring.expiring_soon') }}</small>
                            </div>
                            <div class="col-4">
                                <div class="h4 text-danger" x-text="dashboardData?.token_status_summary?.requiring_attention || 0"></div>
                                <small class="text-muted">{{ __('messages.token_monitoring.need_attention') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('messages.token_monitoring.error_breakdown') }}</h5>
                    </div>
                    <div class="card-body">
                        <template x-if="dashboardData?.performance_metrics?.refresh_operations?.error_breakdown">
                            <div>
                                <template x-for="(count, errorType) in dashboardData.performance_metrics.refresh_operations.error_breakdown" :key="errorType">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-capitalize" x-text="errorType.replace(/_/g, ' ')"></span>
                                        <span class="badge bg-danger" x-text="count"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!dashboardData?.performance_metrics?.refresh_operations?.error_breakdown || Object.keys(dashboardData.performance_metrics.refresh_operations.error_breakdown).length === 0">
                            <div class="text-center text-muted">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <p>{{ __('messages.token_monitoring.no_errors_in_period') }}</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Operations -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('messages.token_monitoring.recent_operations_title') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.token_monitoring.time') }}</th>
                                        <th>{{ __('messages.token_monitoring.user') }}</th>
                                        <th>{{ __('messages.token_monitoring.operation') }}</th>
                                        <th>{{ __('messages.token_monitoring.status') }}</th>
                                        <th>{{ __('messages.token_monitoring.duration') }}</th>
                                        <th>{{ __('messages.token_monitoring.details') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="operation in dashboardData?.recent_operations?.slice(0, 10) || []" :key="operation.id">
                                        <tr>
                                            <td>
                                                <small x-text="formatTimestamp(operation.timestamp)"></small>
                                            </td>
                                            <td>
                                                <span x-text="operation.user_email"></span>
                                            </td>
                                            <td>
                                                <span class="text-capitalize" x-text="operation.type.replace(/_/g, ' ')"></span>
                                            </td>
                                            <td>
                                                <span class="badge" :class="getStatusBadgeClass(operation.status)" x-text="operation.status"></span>
                                            </td>
                                            <td>
                                                <span x-text="operation.duration_ms ? operation.duration_ms + 'ms' : '-'"></span>
                                            </td>
                                            <td>
                                                <small class="text-muted" x-text="operation.error_type || '{{ __('messages.token_monitoring.success') }}'"></small>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="row" x-show="dashboardData?.recommendations?.length > 0">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('messages.token_monitoring.recommendations_title') }}</h5>
                    </div>
                    <div class="card-body">
                        <template x-for="recommendation in dashboardData?.recommendations || []" :key="recommendation.type">
                            <div class="alert" :class="getRecommendationAlertClass(recommendation.severity)" role="alert">
                                <h6 class="alert-heading" x-text="recommendation.title"></h6>
                                <p class="mb-2" x-text="recommendation.description"></p>
                                <template x-if="recommendation.actions?.length > 0">
                                    <div>
                                        <strong>{{ __('messages.token_monitoring.recommended_actions') }}:</strong>
                                        <ul class="mb-0 mt-1">
                                            <template x-for="action in recommendation.actions" :key="action">
                                                <li x-text="action"></li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Error State -->
    <div x-show="error" class="alert alert-danger" role="alert">
        <h6 class="alert-heading">{{ __('messages.token_monitoring.error_loading_dashboard') }}</h6>
        <p class="mb-0" x-text="error"></p>
        <button class="btn btn-outline-danger btn-sm mt-2" @click="refreshDashboard()">
            {{ __('messages.token_monitoring.try_again') }}
        </button>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function tokenMonitoringDashboard() {
    return {
        dashboardData: @json($dashboardData),
        selectedProvider: '{{ $provider }}',
        selectedHours: {{ $hours }},
        isLoading: false,
        error: null,
        chart: null,
        
        initDashboard() {
            this.createPerformanceTrendsChart();
            // Auto-refresh every 30 seconds
            setInterval(() => {
                this.refreshDashboard(false);
            }, 30000);
        },
        
        async refreshDashboard(showLoading = true) {
            if (showLoading) this.isLoading = true;
            this.error = null;
            
            try {
                const response = await fetch(`/admin/token-monitoring/dashboard-data?provider=${this.selectedProvider}&hours=${this.selectedHours}`);
                if (!response.ok) throw new Error('Failed to fetch dashboard data');
                
                this.dashboardData = await response.json();
                this.updatePerformanceTrendsChart();
            } catch (error) {
                this.error = error.message;
                console.error('Dashboard refresh error:', error);
            } finally {
                this.isLoading = false;
            }
        },
        
        createPerformanceTrendsChart() {
            const ctx = document.getElementById('performanceTrendsChart');
            if (!ctx) return;
            
            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Success Rate (%)',
                            data: [],
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.1)',
                            yAxisID: 'y'
                        },
                        {
                            label: 'Avg Duration (ms)',
                            data: [],
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            min: 0,
                            max: 100
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
            
            this.updatePerformanceTrendsChart();
        },
        
        updatePerformanceTrendsChart() {
            if (!this.chart || !this.dashboardData?.health_trends) return;
            
            const trends = this.dashboardData.health_trends;
            const labels = trends.map(t => new Date(t.timestamp).toLocaleTimeString());
            const successRates = trends.map(t => (t.success_rate * 100).toFixed(1));
            const durations = trends.map(t => t.average_duration.toFixed(0));
            
            this.chart.data.labels = labels;
            this.chart.data.datasets[0].data = successRates;
            this.chart.data.datasets[1].data = durations;
            this.chart.update();
        },
        
        async exportData() {
            try {
                const response = await fetch(`/admin/token-monitoring/export?provider=${this.selectedProvider}&format=json`);
                const data = await response.json();
                
                const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `token-monitoring-${this.selectedProvider}-${new Date().toISOString().slice(0, 19)}.json`;
                a.click();
                URL.revokeObjectURL(url);
            } catch (error) {
                console.error('Export error:', error);
                alert('Failed to export data');
            }
        },
        
        // Helper methods for styling
        getSuccessRateCardClass() {
            const rate = this.dashboardData?.overview?.success_rate || 0;
            if (rate >= 0.95) return 'bg-success';
            if (rate >= 0.9) return 'bg-warning';
            return 'bg-danger';
        },
        
        getAlertsCardClass() {
            const alerts = this.dashboardData?.overview?.active_alerts || 0;
            if (alerts === 0) return 'bg-success';
            if (alerts <= 2) return 'bg-warning';
            return 'bg-danger';
        },
        
        getHealthBadgeClass(health) {
            switch (health) {
                case 'healthy': return 'bg-success';
                case 'warning': case 'degraded': return 'bg-warning';
                case 'critical': case 'unhealthy': return 'bg-danger';
                default: return 'bg-secondary';
            }
        },
        
        getOverallHealthClass() {
            const health = this.dashboardData?.performance_metrics?.system_health?.overall_status;
            switch (health) {
                case 'healthy': return 'text-success';
                case 'warning': return 'text-warning';
                case 'critical': return 'text-danger';
                default: return 'text-muted';
            }
        },
        
        getStatusBadgeClass(status) {
            return status === 'success' ? 'bg-success' : 'bg-danger';
        },
        
        getRecommendationAlertClass(severity) {
            switch (severity) {
                case 'critical': return 'alert-danger';
                case 'warning': return 'alert-warning';
                case 'info': return 'alert-info';
                default: return 'alert-secondary';
            }
        },
        
        // Formatting helpers
        formatPercentage(value) {
            return value ? (value * 100).toFixed(1) + '%' : '0%';
        },
        
        formatDuration(ms) {
            if (!ms) return '0ms';
            if (ms < 1000) return Math.round(ms) + 'ms';
            return (ms / 1000).toFixed(1) + 's';
        },
        
        formatTimestamp(timestamp) {
            return timestamp ? new Date(timestamp).toLocaleString() : '-';
        }
    }
}
</script>
@endpush

@push('styles')
<style>
[x-cloak] { display: none !important; }

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: rgba(0, 0, 0, 0.03);
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.opacity-75 {
    opacity: 0.75;
}

.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.75rem;
}

.alert {
    border: none;
    border-left: 4px solid;
}

.alert-danger {
    border-left-color: #dc3545;
}

.alert-warning {
    border-left-color: #ffc107;
}

.alert-info {
    border-left-color: #0dcaf0;
}
</style>
@endpush
@endsection