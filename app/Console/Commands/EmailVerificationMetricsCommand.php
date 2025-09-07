<?php

namespace App\Console\Commands;

use App\Services\EmailVerificationMetricsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EmailVerificationMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'email-verification:metrics 
                            {--hours=24 : Number of hours to analyze}
                            {--format=table : Output format (table, json, log)}
                            {--alerts : Show only unusual activity alerts}';

    /**
     * The console command description.
     */
    protected $description = 'Display email verification metrics and bypass patterns';

    /**
     * Execute the console command.
     */
    public function handle(EmailVerificationMetricsService $metricsService): int
    {
        $hours = (int) $this->option('hours');
        $format = $this->option('format');
        $alertsOnly = $this->option('alerts');

        $this->info("Email Verification Metrics - Last {$hours} hours");
        $this->line('');

        if ($alertsOnly) {
            $this->displayAlerts($metricsService);
            return Command::SUCCESS;
        }

        $dashboardMetrics = $metricsService->getDashboardMetrics();
        $bypassPatterns = $metricsService->getBypassPatterns($hours);
        $restrictionPatterns = $metricsService->getRestrictionPatterns($hours);

        switch ($format) {
            case 'json':
                $this->displayJsonFormat($dashboardMetrics, $bypassPatterns, $restrictionPatterns);
                break;
            case 'log':
                $this->logMetrics($dashboardMetrics, $bypassPatterns, $restrictionPatterns);
                break;
            default:
                $this->displayTableFormat($dashboardMetrics, $bypassPatterns, $restrictionPatterns);
        }

        return Command::SUCCESS;
    }

    /**
     * Display metrics in table format
     */
    private function displayTableFormat(array $dashboard, array $bypasses, array $restrictions): void
    {
        // Dashboard Summary
        $this->info('📊 Dashboard Summary');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Existing User Bypasses', $dashboard['last_24_hours']['existing_user_bypasses']],
                ['Restriction Enforcements', $dashboard['last_24_hours']['restriction_enforcements']],
                ['Bypass/Restriction Ratio', $dashboard['last_24_hours']['bypass_to_restriction_ratio']],
            ]
        );
        $this->line('');

        // Bypass Patterns
        $this->info('🔓 Bypass Patterns');
        if (!empty($bypasses['bypasses_by_role'])) {
            $this->line('By Role:');
            $this->table(
                ['Role', 'Count'],
                collect($bypasses['bypasses_by_role'])->map(fn($count, $role) => [$role, $count])->toArray()
            );
        }

        if (!empty($bypasses['bypasses_by_restriction_type'])) {
            $this->line('By Restriction Type:');
            $this->table(
                ['Restriction', 'Bypasses'],
                collect($bypasses['bypasses_by_restriction_type'])->map(fn($count, $type) => [$type, $count])->toArray()
            );
        }
        $this->line('');

        // Restriction Patterns
        $this->info('🚫 Restriction Enforcement');
        if (!empty($restrictions['restrictions_by_type'])) {
            $this->table(
                ['Restriction Type', 'Count'],
                collect($restrictions['restrictions_by_type'])->map(fn($count, $type) => [$type, $count])->toArray()
            );
        }

        if (!empty($restrictions['blocked_domains'])) {
            $this->line('Top Blocked Domains:');
            $this->table(
                ['Domain', 'Blocks'],
                collect($restrictions['blocked_domains'])->take(10)->map(fn($count, $domain) => [$domain, $count])->toArray()
            );
        }
        $this->line('');

        // Unusual Activity
        $this->displayUnusualActivity($bypasses['unusual_patterns'] ?? []);
    }

    /**
     * Display metrics in JSON format
     */
    private function displayJsonFormat(array $dashboard, array $bypasses, array $restrictions): void
    {
        $output = [
            'dashboard' => $dashboard,
            'bypass_patterns' => $bypasses,
            'restriction_patterns' => $restrictions,
            'generated_at' => now()->toISOString()
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT));
    }

    /**
     * Log metrics to Laravel log
     */
    private function logMetrics(array $dashboard, array $bypasses, array $restrictions): void
    {
        Log::info('Email verification metrics report', [
            'dashboard_metrics' => $dashboard,
            'bypass_patterns' => $bypasses,
            'restriction_patterns' => $restrictions,
            'context' => 'email_verification_metrics_report'
        ]);

        $this->info('Metrics logged to Laravel log file');
    }

    /**
     * Display only alerts
     */
    private function displayAlerts(EmailVerificationMetricsService $metricsService): void
    {
        $bypassPatterns = $metricsService->getBypassPatterns(24);
        $alerts = $bypassPatterns['unusual_patterns'] ?? [];

        if (empty($alerts)) {
            $this->info('✅ No unusual activity detected in the last 24 hours');
            return;
        }

        $this->warn('⚠️  Unusual Activity Detected');
        $this->displayUnusualActivity($alerts);
    }

    /**
     * Display unusual activity alerts
     */
    private function displayUnusualActivity(array $alerts): void
    {
        if (empty($alerts)) {
            $this->info('✅ No unusual activity detected');
            return;
        }

        $this->warn('⚠️  Unusual Activity Alerts');
        
        $alertData = [];
        foreach ($alerts as $alert) {
            $severity = match($alert['severity']) {
                'warning' => '🟡',
                'error' => '🔴',
                default => '🔵'
            };
            
            $alertData[] = [
                $severity . ' ' . $alert['type'],
                $alert['message'],
                $alert['severity']
            ];
        }

        $this->table(['Type', 'Message', 'Severity'], $alertData);
        $this->line('');
    }
}