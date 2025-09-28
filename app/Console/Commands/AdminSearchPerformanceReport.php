<?php

namespace App\Console\Commands;

use App\Services\SearchPerformanceMonitoringService;
use Illuminate\Console\Command;

class AdminSearchPerformanceReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:search-performance-report 
                            {--clear : Clear performance monitoring data after generating report}
                            {--json : Output report in JSON format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a performance report for admin user search functionality';

    protected SearchPerformanceMonitoringService $monitoringService;

    public function __construct(SearchPerformanceMonitoringService $monitoringService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📊 Admin User Search Performance Report');
        $this->info('=====================================');
        $this->newLine();

        $stats = $this->monitoringService->getPerformanceStats();
        $insights = $this->monitoringService->getDatabaseInsights();
        $recommendations = $this->monitoringService->getOptimizationRecommendations();

        if ($this->option('json')) {
            $this->outputJsonReport($stats, $insights, $recommendations);
            return;
        }

        $this->displayTextReport($stats, $insights, $recommendations);

        if ($this->option('clear')) {
            $this->monitoringService->clearMetrics();
            $this->info('✅ Performance monitoring data cleared');
        }
    }

    /**
     * Display report in text format.
     */
    private function displayTextReport(array $stats, array $insights, array $recommendations): void
    {
        // Performance Statistics
        $this->info('🔍 Search Performance Statistics');
        $this->info('-------------------------------');
        $this->line("Total Searches: {$stats['total_searches']}");
        $this->line("Average Execution Time: {$stats['average_execution_time_ms']}ms");
        $this->line("Slow Queries: {$stats['slow_query_count']} ({$stats['slow_query_percentage']}%)");
        $this->line("Monitoring Window: {$stats['monitoring_window_minutes']} minutes");
        $this->newLine();

        // Most Common Search Terms
        if (!empty($stats['most_common_terms'])) {
            $this->info('🔤 Most Common Search Terms');
            $this->info('---------------------------');
            foreach ($stats['most_common_terms'] as $term => $count) {
                $this->line("  '{$term}': {$count} searches");
            }
            $this->newLine();
        }

        // Database Insights
        if (!empty($insights)) {
            $this->info('💾 Database Insights');
            $this->info('-------------------');
            
            if (isset($insights['table_stats'])) {
                $tableStats = $insights['table_stats'];
                $this->line("User Table Rows: " . number_format($tableStats['row_count']));
                $this->line("Data Size: {$tableStats['data_size_mb']} MB");
                $this->line("Index Size: {$tableStats['index_size_mb']} MB");
            }

            if (isset($insights['search_indexes'])) {
                $this->newLine();
                $this->line('Search Indexes:');
                foreach ($insights['search_indexes'] as $index) {
                    $this->line("  - {$index['name']} ({$index['column']}): " . number_format($index['cardinality']) . " cardinality");
                }
            }

            if (isset($insights['error'])) {
                $this->warn("Database insights error: {$insights['error']}");
            }
            
            $this->newLine();
        }

        // Optimization Recommendations
        if (!empty($recommendations)) {
            $this->info('💡 Optimization Recommendations');
            $this->info('-------------------------------');
            
            foreach ($recommendations as $rec) {
                $priority = strtoupper($rec['priority']);
                $icon = match($rec['priority']) {
                    'high' => '🔴',
                    'medium' => '🟡',
                    'low' => '🟢',
                    default => '⚪'
                };
                
                $this->line("{$icon} [{$priority}] {$rec['message']}");
                $this->line("   Action: {$rec['action']}");
                $this->newLine();
            }
        } else {
            $this->info('✅ No optimization recommendations at this time');
            $this->newLine();
        }

        // Performance Status Summary
        $this->displayPerformanceStatus($stats);
    }

    /**
     * Output report in JSON format.
     */
    private function outputJsonReport(array $stats, array $insights, array $recommendations): void
    {
        $report = [
            'timestamp' => now()->toISOString(),
            'performance_stats' => $stats,
            'database_insights' => $insights,
            'recommendations' => $recommendations
        ];

        $this->line(json_encode($report, JSON_PRETTY_PRINT));
    }

    /**
     * Display overall performance status.
     */
    private function displayPerformanceStatus(array $stats): void
    {
        $this->info('🎯 Performance Status');
        $this->info('--------------------');

        if ($stats['total_searches'] === 0) {
            $this->warn('No search data available. Perform some searches to generate metrics.');
            return;
        }

        $status = 'GOOD';
        $color = 'info';

        if ($stats['average_execution_time_ms'] > 100) {
            $status = 'POOR';
            $color = 'error';
        } elseif ($stats['average_execution_time_ms'] > 50 || $stats['slow_query_percentage'] > 10) {
            $status = 'FAIR';
            $color = 'warn';
        }

        $this->line("Overall Status: ", false);
        $this->{$color}($status);

        $benchmarks = [
            'Excellent' => '< 25ms average',
            'Good' => '25-50ms average',
            'Fair' => '50-100ms average',
            'Poor' => '> 100ms average'
        ];

        $this->newLine();
        $this->line('Performance Benchmarks:');
        foreach ($benchmarks as $level => $benchmark) {
            $this->line("  {$level}: {$benchmark}");
        }
    }
}