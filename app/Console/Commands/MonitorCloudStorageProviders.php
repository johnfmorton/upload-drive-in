<?php

namespace App\Console\Commands;

use App\Services\CloudStorageProviderMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorCloudStorageProviders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cloud-storage:monitor 
                            {--json : Output results in JSON format}
                            {--alert : Send alerts for issues found}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor cloud storage providers and send alerts for issues';

    public function __construct(
        private readonly CloudStorageProviderMonitoringService $monitoringService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $jsonOutput = $this->option('json');
        $quiet = $this->option('quiet'); // Built-in Laravel option
        $sendAlerts = $this->option('alert');

        if (!$jsonOutput && !$quiet) {
            $this->info('🔍 Cloud Storage Provider Monitoring');
            $this->info('===================================');
            $this->newLine();
        }

        try {
            $results = $this->monitoringService->monitorAllProviders();
            
            // Cache results for API access
            $this->monitoringService->cacheMonitoringResults($results);

            if ($jsonOutput) {
                $this->line(json_encode($results, JSON_PRETTY_PRINT));
            } elseif (!$quiet) {
                $this->displayResults($results);
            }

            // Return appropriate exit code
            $criticalIssues = $results['summary']['critical_issues'] ?? 0;
            $warningIssues = $results['summary']['warning_issues'] ?? 0;

            if ($criticalIssues > 0) {
                return self::FAILURE; // Critical issues
            } elseif ($warningIssues > 0) {
                return 1; // Warning exit code
            } else {
                return self::SUCCESS; // All good
            }

        } catch (\Exception $e) {
            if ($jsonOutput) {
                $this->line(json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], JSON_PRETTY_PRINT));
            } else {
                $this->error('❌ Monitoring failed: ' . $e->getMessage());
            }

            Log::error('Cloud storage monitoring command failed', [
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Display monitoring results
     */
    private function displayResults(array $results): void
    {
        // Summary
        $this->info('📊 Monitoring Summary:');
        $summary = $results['summary'];
        
        $this->table(
            ['Metric', 'Count'],
            [
                ['Providers Monitored', $results['providers_monitored']],
                ['Users Monitored', $results['users_monitored']],
                ['Healthy Providers', $summary['healthy_providers']],
                ['Providers Down', $summary['providers_down']],
                ['Users Affected', $summary['users_affected']],
                ['Critical Issues', $summary['critical_issues']],
                ['Warning Issues', $summary['warning_issues']],
                ['Alerts Sent', $results['alerts_sent']],
            ]
        );
        $this->newLine();

        // Overall status
        $overallStatus = $this->determineOverallStatus($summary);
        $statusIcon = match ($overallStatus) {
            'healthy' => '✅',
            'warning' => '⚠️',
            'critical' => '🚨',
            default => '❓',
        };

        $this->info("Overall Status: {$statusIcon} " . strtoupper($overallStatus));
        $this->newLine();

        // Issues
        if (!empty($results['issues_detected']['critical'])) {
            $this->error('🚨 Critical Issues:');
            foreach ($results['issues_detected']['critical'] as $issue) {
                $this->line("  • {$issue['message']}");
                if (isset($issue['details']) && is_array($issue['details'])) {
                    foreach ($issue['details'] as $detail) {
                        $this->line("    - {$detail}");
                    }
                }
            }
            $this->newLine();
        }

        if (!empty($results['issues_detected']['warning'])) {
            $this->warn('⚠️  Warning Issues:');
            foreach ($results['issues_detected']['warning'] as $issue) {
                $this->line("  • {$issue['message']}");
                if (isset($issue['details']) && is_array($issue['details'])) {
                    foreach ($issue['details'] as $detail) {
                        $this->line("    - {$detail}");
                    }
                }
            }
            $this->newLine();
        }

        // Recommendations
        if ($summary['critical_issues'] > 0 || $summary['warning_issues'] > 0) {
            $this->warn('💡 Recommendations:');
            
            if ($summary['providers_down'] > 0) {
                $this->line('  • Check provider configurations and connectivity');
                $this->line('  • Review provider service status pages');
                $this->line('  • Consider enabling fallback providers');
            }
            
            if ($summary['users_affected'] > 0) {
                $this->line('  • Notify affected users about connection issues');
                $this->line('  • Provide reconnection instructions');
                $this->line('  • Monitor user upload success rates');
            }
            
            if ($summary['critical_issues'] > 0) {
                $this->line('  • Investigate critical issues immediately');
                $this->line('  • Consider maintenance mode if all providers are down');
                $this->line('  • Check system logs for detailed error information');
            }
            
            $this->newLine();
        }

        // Next steps
        if ($results['alerts_sent'] > 0) {
            $this->info("📧 {$results['alerts_sent']} alerts have been sent to relevant users and administrators");
        } else {
            $this->info('✅ No alerts needed - all systems operating normally');
        }
    }

    /**
     * Determine overall status from summary
     */
    private function determineOverallStatus(array $summary): string
    {
        if ($summary['critical_issues'] > 0) {
            return 'critical';
        }
        
        if ($summary['warning_issues'] > 0) {
            return 'warning';
        }
        
        return 'healthy';
    }
}