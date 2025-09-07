<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UserLookupPerformanceService;
use App\Services\DomainRulesCacheService;

class UserLookupPerformanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user-lookup:performance 
                            {action=report : Action to perform (report|clear|health|cache-stats)}
                            {--format=table : Output format (table|json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor and manage user lookup performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $format = $this->option('format');

        switch ($action) {
            case 'report':
                return $this->showPerformanceReport($format);
            
            case 'clear':
                return $this->clearPerformanceStats();
            
            case 'health':
                return $this->checkPerformanceHealth($format);
            
            case 'cache-stats':
                return $this->showCacheStats($format);
            
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: report, clear, health, cache-stats');
                return 1;
        }
    }

    /**
     * Show performance report
     *
     * @param string $format
     * @return int
     */
    private function showPerformanceReport(string $format): int
    {
        $performanceService = app(UserLookupPerformanceService::class);
        $stats = $performanceService->getPerformanceStats();

        if (empty($stats) || ($stats['total_lookups'] ?? 0) === 0) {
            $this->info('No performance data available. User lookups need to be performed first.');
            return 0;
        }

        if ($format === 'json') {
            $this->line(json_encode($stats, JSON_PRETTY_PRINT));
            return 0;
        }

        $this->info('User Lookup Performance Report');
        $this->info('================================');

        $tableData = [
            ['Metric', 'Value'],
            ['Total Lookups', number_format($stats['total_lookups'])],
            ['Successful Lookups', number_format($stats['successful_lookups'])],
            ['Failed Lookups', number_format($stats['failed_lookups'])],
            ['Success Rate', $stats['success_rate'] . '%'],
            ['Average Time (ms)', $stats['average_time_ms']],
            ['Min Time (ms)', round($stats['min_time_ms'], 2)],
            ['Max Time (ms)', round($stats['max_time_ms'], 2)],
            ['Slow Queries', number_format($stats['slow_queries'])],
            ['Slow Query Rate', $stats['slow_query_rate'] . '%'],
            ['Average Queries per Lookup', $stats['average_queries_per_lookup']],
            ['Last Updated', $stats['last_updated']],
        ];

        $this->table(['Metric', 'Value'], array_slice($tableData, 1));

        return 0;
    }

    /**
     * Clear performance statistics
     *
     * @return int
     */
    private function clearPerformanceStats(): int
    {
        $performanceService = app(UserLookupPerformanceService::class);
        $performanceService->clearPerformanceStats();

        $this->info('Performance statistics cleared successfully.');
        return 0;
    }

    /**
     * Check performance health
     *
     * @param string $format
     * @return int
     */
    private function checkPerformanceHealth(string $format): int
    {
        $performanceService = app(UserLookupPerformanceService::class);
        $health = $performanceService->checkPerformanceHealth();

        if ($format === 'json') {
            $this->line(json_encode($health, JSON_PRETTY_PRINT));
            return $health['status'] === 'healthy' ? 0 : 1;
        }

        $this->info('User Lookup Performance Health Check');
        $this->info('====================================');

        // Status
        $statusColor = match($health['status']) {
            'healthy' => 'green',
            'needs_attention' => 'yellow',
            'no_data' => 'blue',
            default => 'red'
        };

        $this->line("<fg={$statusColor}>Status: " . strtoupper($health['status']) . "</>");

        // Issues
        if (!empty($health['issues'])) {
            $this->warn('Issues Found:');
            foreach ($health['issues'] as $issue) {
                $this->line("  • {$issue}");
            }
        }

        // Recommendations
        if (!empty($health['recommendations'])) {
            $this->info('Recommendations:');
            foreach ($health['recommendations'] as $recommendation) {
                $this->line("  • {$recommendation}");
            }
        }

        // Stats summary
        if (isset($health['stats']) && !empty($health['stats'])) {
            $this->info('Performance Summary:');
            $stats = $health['stats'];
            $this->line("  • Total Lookups: " . number_format($stats['total_lookups'] ?? 0));
            $this->line("  • Average Time: " . ($stats['average_time_ms'] ?? 'N/A') . ' ms');
            $this->line("  • Success Rate: " . ($stats['success_rate'] ?? 'N/A') . '%');
        }

        return $health['status'] === 'healthy' ? 0 : 1;
    }

    /**
     * Show cache statistics
     *
     * @param string $format
     * @return int
     */
    private function showCacheStats(string $format): int
    {
        $cacheService = app(DomainRulesCacheService::class);
        $stats = $cacheService->getCacheStats();

        if ($format === 'json') {
            $this->line(json_encode($stats, JSON_PRETTY_PRINT));
            return 0;
        }

        $this->info('Domain Rules Cache Statistics');
        $this->info('==============================');

        $tableData = [
            ['Cache Key', $stats['cache_key']],
            ['Cache TTL (seconds)', $stats['cache_ttl']],
            ['Cache Hit', $stats['cache_hit'] ? 'Yes' : 'No'],
            ['Rules Loaded', $stats['rules_loaded'] ? 'Yes' : 'No'],
        ];

        if ($stats['rules_config']) {
            $config = $stats['rules_config'];
            $tableData[] = ['Rules Mode', $config['mode']];
            $tableData[] = ['Public Registration', $config['allow_public_registration'] ? 'Enabled' : 'Disabled'];
            $tableData[] = ['Rules Count', $config['rules_count']];
        }

        $this->table(['Setting', 'Value'], $tableData);

        return 0;
    }
}