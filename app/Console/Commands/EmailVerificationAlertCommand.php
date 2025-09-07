<?php

namespace App\Console\Commands;

use App\Services\EmailVerificationMetricsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class EmailVerificationAlertCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'email-verification:check-alerts 
                            {--threshold-bypasses=20 : Alert threshold for bypasses per hour}
                            {--threshold-restrictions=50 : Alert threshold for restrictions per hour}
                            {--notify-email= : Email address to send alerts to}';

    /**
     * The console command description.
     */
    protected $description = 'Check for unusual email verification patterns and send alerts';

    private const ALERT_CACHE_KEY = 'email_verification_last_alert';
    private const ALERT_COOLDOWN = 3600; // 1 hour cooldown between alerts

    /**
     * Execute the console command.
     */
    public function handle(EmailVerificationMetricsService $metricsService): int
    {
        $bypassThreshold = (int) $this->option('threshold-bypasses');
        $restrictionThreshold = (int) $this->option('threshold-restrictions');
        $notifyEmail = $this->option('notify-email');

        // Get recent metrics
        $bypassPatterns = $metricsService->getBypassPatterns(1); // Last hour
        $restrictionPatterns = $metricsService->getRestrictionPatterns(1); // Last hour
        $dashboardMetrics = $metricsService->getDashboardMetrics();

        $alerts = [];

        // Check for bypass threshold
        if ($bypassPatterns['total_bypasses'] > $bypassThreshold) {
            $alerts[] = [
                'type' => 'high_bypass_volume',
                'severity' => 'warning',
                'message' => "High volume of existing user bypasses: {$bypassPatterns['total_bypasses']} in the last hour (threshold: {$bypassThreshold})",
                'data' => [
                    'count' => $bypassPatterns['total_bypasses'],
                    'threshold' => $bypassThreshold,
                    'by_role' => $bypassPatterns['bypasses_by_role'] ?? []
                ]
            ];
        }

        // Check for restriction threshold
        if ($restrictionPatterns['total_restrictions'] > $restrictionThreshold) {
            $alerts[] = [
                'type' => 'high_restriction_volume',
                'severity' => 'warning',
                'message' => "High volume of restriction enforcements: {$restrictionPatterns['total_restrictions']} in the last hour (threshold: {$restrictionThreshold})",
                'data' => [
                    'count' => $restrictionPatterns['total_restrictions'],
                    'threshold' => $restrictionThreshold,
                    'by_type' => $restrictionPatterns['restrictions_by_type'] ?? []
                ]
            ];
        }

        // Add unusual pattern alerts
        $unusualAlerts = $bypassPatterns['unusual_patterns'] ?? [];
        foreach ($unusualAlerts as $alert) {
            if ($alert['severity'] === 'warning') {
                $alerts[] = $alert;
            }
        }

        // Check for zero activity (might indicate system issues)
        $totalActivity = $bypassPatterns['total_bypasses'] + $restrictionPatterns['total_restrictions'];
        if ($totalActivity === 0 && $this->isBusinessHours()) {
            $alerts[] = [
                'type' => 'no_activity',
                'severity' => 'info',
                'message' => 'No email verification activity detected during business hours - possible system issue',
                'data' => ['hour' => now()->format('H:i')]
            ];
        }

        if (empty($alerts)) {
            $this->info('✅ No alerts detected');
            return Command::SUCCESS;
        }

        // Process alerts
        $this->processAlerts($alerts, $notifyEmail, $dashboardMetrics);

        return Command::SUCCESS;
    }

    /**
     * Process and handle alerts
     */
    private function processAlerts(array $alerts, ?string $notifyEmail, array $dashboardMetrics): void
    {
        // Check cooldown
        $lastAlert = Cache::get(self::ALERT_CACHE_KEY);
        if ($lastAlert && now()->diffInSeconds($lastAlert) < self::ALERT_COOLDOWN) {
            $this->info('Alert cooldown active, skipping notifications');
            return;
        }

        // Log all alerts
        foreach ($alerts as $alert) {
            Log::warning('Email verification alert triggered', [
                'alert' => $alert,
                'dashboard_metrics' => $dashboardMetrics,
                'context' => 'email_verification_alert'
            ]);

            $this->displayAlert($alert);
        }

        // Send email notification if configured
        if ($notifyEmail && $this->shouldSendEmailAlert($alerts)) {
            $this->sendEmailAlert($notifyEmail, $alerts, $dashboardMetrics);
        }

        // Update last alert time
        Cache::put(self::ALERT_CACHE_KEY, now(), self::ALERT_COOLDOWN);
    }

    /**
     * Display alert in console
     */
    private function displayAlert(array $alert): void
    {
        $icon = match($alert['severity']) {
            'warning' => '⚠️',
            'error' => '🚨',
            default => 'ℹ️'
        };

        $this->line("{$icon} [{$alert['type']}] {$alert['message']}");
    }

    /**
     * Check if we should send email alert
     */
    private function shouldSendEmailAlert(array $alerts): bool
    {
        // Send email for warning or error level alerts
        return collect($alerts)->contains(function ($alert) {
            return in_array($alert['severity'], ['warning', 'error']);
        });
    }

    /**
     * Send email alert
     */
    private function sendEmailAlert(string $email, array $alerts, array $dashboardMetrics): void
    {
        try {
            $subject = 'Email Verification System Alert';
            $highSeverityAlerts = collect($alerts)->where('severity', 'warning')->count();
            
            if ($highSeverityAlerts > 0) {
                $subject .= " - {$highSeverityAlerts} Warning(s)";
            }

            $body = $this->buildEmailBody($alerts, $dashboardMetrics);

            // Simple mail sending - in a real implementation, you'd create a proper Mailable
            Mail::raw($body, function ($message) use ($email, $subject) {
                $message->to($email)
                        ->subject($subject);
            });

            $this->info("📧 Alert email sent to {$email}");
            
            Log::info('Email verification alert sent', [
                'recipient' => $email,
                'alert_count' => count($alerts),
                'context' => 'email_verification_alert'
            ]);

        } catch (\Exception $e) {
            $this->error("Failed to send alert email: {$e->getMessage()}");
            
            Log::error('Failed to send email verification alert', [
                'error' => $e->getMessage(),
                'recipient' => $email,
                'context' => 'email_verification_alert'
            ]);
        }
    }

    /**
     * Build email body for alert
     */
    private function buildEmailBody(array $alerts, array $dashboardMetrics): string
    {
        $body = "Email Verification System Alert\n";
        $body .= "Generated at: " . now()->format('Y-m-d H:i:s T') . "\n\n";

        $body .= "DASHBOARD SUMMARY (Last 24 hours):\n";
        $body .= "- Existing User Bypasses: " . $dashboardMetrics['last_24_hours']['existing_user_bypasses'] . "\n";
        $body .= "- Restriction Enforcements: " . $dashboardMetrics['last_24_hours']['restriction_enforcements'] . "\n";
        $body .= "- Bypass/Restriction Ratio: " . $dashboardMetrics['last_24_hours']['bypass_to_restriction_ratio'] . "\n\n";

        $body .= "ALERTS:\n";
        foreach ($alerts as $i => $alert) {
            $body .= ($i + 1) . ". [{$alert['severity']}] {$alert['type']}\n";
            $body .= "   {$alert['message']}\n";
            
            if (isset($alert['data'])) {
                $body .= "   Data: " . json_encode($alert['data']) . "\n";
            }
            $body .= "\n";
        }

        $body .= "Please review the email verification system for any issues.\n";
        $body .= "You can run 'php artisan email-verification:metrics --alerts' for more details.\n";

        return $body;
    }

    /**
     * Check if current time is during business hours
     */
    private function isBusinessHours(): bool
    {
        $hour = now()->hour;
        $dayOfWeek = now()->dayOfWeek;
        
        // Monday-Friday, 9 AM - 5 PM
        return $dayOfWeek >= 1 && $dayOfWeek <= 5 && $hour >= 9 && $hour <= 17;
    }
}