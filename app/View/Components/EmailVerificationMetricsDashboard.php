<?php

namespace App\View\Components;

use App\Services\EmailVerificationMetricsService;
use Illuminate\View\Component;
use Illuminate\View\View;

class EmailVerificationMetricsDashboard extends Component
{
    public array $metrics;
    public array $bypassPatterns;
    public array $restrictionPatterns;
    public array $alerts;

    /**
     * Create a new component instance.
     */
    public function __construct(
        private EmailVerificationMetricsService $metricsService,
        public int $hours = 24
    ) {
        $this->metrics = $this->metricsService->getDashboardMetrics();
        $this->bypassPatterns = $this->metricsService->getBypassPatterns($this->hours);
        $this->restrictionPatterns = $this->metricsService->getRestrictionPatterns($this->hours);
        $this->alerts = $this->bypassPatterns['unusual_patterns'] ?? [];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.email-verification-metrics-dashboard');
    }

    /**
     * Get formatted bypass ratio
     */
    public function getBypassRatio(): string
    {
        $ratio = $this->metrics['last_24_hours']['bypass_to_restriction_ratio'];
        
        if ($ratio === PHP_FLOAT_MAX) {
            return __('messages.email_verification_dashboard_all_bypasses');
        }
        
        if ($ratio === 0) {
            return __('messages.email_verification_dashboard_no_bypasses');
        }
        
        return number_format($ratio, 2) . ':1';
    }

    /**
     * Get alert severity color class
     */
    public function getAlertColorClass(string $severity): string
    {
        return match($severity) {
            'error' => 'text-red-600 bg-red-50 border-red-200',
            'warning' => 'text-yellow-600 bg-yellow-50 border-yellow-200',
            default => 'text-blue-600 bg-blue-50 border-blue-200'
        };
    }

    /**
     * Get alert icon
     */
    public function getAlertIcon(string $severity): string
    {
        return match($severity) {
            'error' => '🚨',
            'warning' => '⚠️',
            default => 'ℹ️'
        };
    }

    /**
     * Format large numbers
     */
    public function formatNumber(int $number): string
    {
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1) . 'M';
        }
        
        if ($number >= 1000) {
            return number_format($number / 1000, 1) . 'K';
        }
        
        return number_format($number);
    }

    /**
     * Get top items from array
     */
    public function getTopItems(array $items, int $limit = 5): array
    {
        return array_slice($items, 0, $limit, true);
    }

    /**
     * Check if there are high-severity alerts
     */
    public function hasHighSeverityAlerts(): bool
    {
        return collect($this->alerts)->contains(function ($alert) {
            return in_array($alert['severity'], ['warning', 'error']);
        });
    }

    /**
     * Get summary status
     */
    public function getSummaryStatus(): array
    {
        $totalActivity = $this->metrics['last_24_hours']['existing_user_bypasses'] + 
                        $this->metrics['last_24_hours']['restriction_enforcements'];
        
        $status = 'normal';
        $message = __('messages.email_verification_dashboard_system_normal');
        
        if ($this->hasHighSeverityAlerts()) {
            $status = 'warning';
            $message = __('messages.email_verification_dashboard_unusual_activity');
        }
        
        if ($totalActivity === 0) {
            $status = 'info';
            $message = __('messages.email_verification_dashboard_no_recent_activity');
        }
        
        if ($this->metrics['last_24_hours']['existing_user_bypasses'] > 50) {
            $status = 'warning';
            $message = __('messages.email_verification_dashboard_high_bypass_volume');
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'color_class' => match($status) {
                'warning' => 'text-yellow-600 bg-yellow-50 border-yellow-200',
                'error' => 'text-red-600 bg-red-50 border-red-200',
                'info' => 'text-blue-600 bg-blue-50 border-blue-200',
                default => 'text-green-600 bg-green-50 border-green-200'
            }
        ];
    }
}