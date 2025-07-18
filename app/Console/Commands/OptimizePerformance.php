<?php

namespace App\Console\Commands;

use App\Services\PerformanceOptimizationService;
use Illuminate\Console\Command;

class OptimizePerformance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'performance:optimize 
                            {--report : Generate performance report only}
                            {--cache-only : Only optimize caches}
                            {--cleanup-only : Only run cleanup tasks}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize application performance through caching, cleanup, and analysis';

    public function __construct(
        private PerformanceOptimizationService $optimizationService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting performance optimization...');
        
        if ($this->option('report')) {
            return $this->generateReport();
        }
        
        if ($this->option('cache-only')) {
            return $this->optimizeCacheOnly();
        }
        
        if ($this->option('cleanup-only')) {
            return $this->cleanupOnly();
        }
        
        return $this->runFullOptimization();
    }
    
    private function generateReport(): int
    {
        $this->info('📊 Generating performance report...');
        
        $comparison = $this->optimizationService->getPerformanceComparison();
        
        if ($comparison['comparison_available']) {
            $this->displayPerformanceComparison($comparison);
        } else {
            $this->displayCurrentMetrics($comparison['current']);
        }
        
        return Command::SUCCESS;
    }
    
    private function optimizeCacheOnly(): int
    {
        $this->info('🗄️ Optimizing caches...');
        
        $results = $this->optimizationService->warmUpCaches();
        
        $this->table(
            ['Cache Type', 'Status'],
            [
                ['File Metadata', $results['metadata_cached'] . ' files cached'],
                ['Thumbnails', $results['thumbnails_cached'] . ' thumbnails generated'],
                ['Statistics', $results['statistics_cached'] ? '✓ Cached' : '✗ Failed'],
                ['Filter Options', $results['filters_cached'] ? '✓ Cached' : '✗ Failed'],
            ]
        );
        
        $this->info('✅ Cache optimization completed!');
        
        return Command::SUCCESS;
    }
    
    private function cleanupOnly(): int
    {
        $this->info('🧹 Running cleanup tasks...');
        
        $results = $this->optimizationService->cleanupOldData();
        
        $this->table(
            ['Cleanup Task', 'Result'],
            [
                ['Temporary Files', $results['temp_files_cleaned'] . ' files removed'],
                ['Old Logs', $results['old_logs_cleaned'] . ' entries cleaned'],
                ['Cache Entries', $results['cache_entries_cleaned'] . ' entries cleaned'],
            ]
        );
        
        $this->info('✅ Cleanup completed!');
        
        return Command::SUCCESS;
    }
    
    private function runFullOptimization(): int
    {
        $progressBar = $this->output->createProgressBar(4);
        $progressBar->setFormat('verbose');
        
        $progressBar->setMessage('Analyzing database performance...');
        $progressBar->start();
        
        $results = $this->optimizationService->optimizePerformance();
        
        $progressBar->advance();
        $progressBar->setMessage('Warming up caches...');
        $progressBar->advance();
        
        $progressBar->setMessage('Running cleanup tasks...');
        $progressBar->advance();
        
        $progressBar->setMessage('Generating metrics...');
        $progressBar->advance();
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Display results
        $this->displayOptimizationResults($results);
        
        $this->info("✅ Performance optimization completed in {$results['optimization_time']} seconds!");
        
        return Command::SUCCESS;
    }
    
    private function displayOptimizationResults(array $results): void
    {
        $this->info('📈 Optimization Results:');
        $this->newLine();
        
        // Database optimization
        $this->info('🗃️ Database Optimization:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Queries Analyzed', $results['database']['analyzed_queries']],
                ['Slow Queries Found', $results['database']['slow_queries']],
                ['Optimizations Applied', $results['database']['optimizations_applied']],
            ]
        );
        
        // Cache optimization
        $this->info('🗄️ Cache Optimization:');
        $this->table(
            ['Cache Type', 'Result'],
            [
                ['File Metadata', $results['cache']['metadata_cached'] . ' files'],
                ['Thumbnails', $results['cache']['thumbnails_cached'] . ' images'],
                ['Statistics', $results['cache']['statistics_cached'] ? '✓' : '✗'],
                ['Filter Options', $results['cache']['filters_cached'] ? '✓' : '✗'],
            ]
        );
        
        // Cleanup results
        $this->info('🧹 Cleanup Results:');
        $this->table(
            ['Task', 'Items Cleaned'],
            [
                ['Temporary Files', $results['cleanup']['temp_files_cleaned']],
                ['Old Logs', $results['cleanup']['old_logs_cleaned']],
                ['Cache Entries', $results['cleanup']['cache_entries_cleaned']],
            ]
        );
        
        // Performance metrics
        $this->displayCurrentMetrics($results['metrics']);
    }
    
    private function displayCurrentMetrics(array $metrics): void
    {
        $this->info('📊 Current Performance Metrics:');
        $this->newLine();
        
        // Database metrics
        $this->info('🗃️ Database:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Files', number_format($metrics['database']['total_files'])],
                ['Query Time', $metrics['database']['query_time_ms'] . ' ms'],
                ['Table Size', $metrics['database']['table_size_mb'] . ' MB'],
                ['Connections', $metrics['database']['connection_count']],
            ]
        );
        
        // Cache metrics
        $this->info('🗄️ Cache:');
        $this->table(
            ['Cache Type', 'Status'],
            [
                ['File Statistics', $metrics['cache']['file_statistics_cached'] ? '✓ Cached' : '✗ Not cached'],
                ['Filter Options', $metrics['cache']['filter_options_cached'] ? '✓ Cached' : '✗ Not cached'],
                ['Metadata Entries', number_format($metrics['cache']['metadata_cache_count'])],
                ['Thumbnail Entries', number_format($metrics['cache']['thumbnail_cache_count'])],
            ]
        );
        
        // Storage metrics
        $this->info('💾 Storage:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Uploads Directory', $metrics['storage']['uploads_directory_size_mb'] . ' MB'],
                ['Temp Directory', $metrics['storage']['temp_directory_size_mb'] . ' MB'],
                ['Local Files', number_format($metrics['storage']['total_local_files'])],
                ['Free Space', $metrics['storage']['disk_free_space_gb'] . ' GB'],
            ]
        );
        
        // Memory metrics
        $this->info('🧠 Memory:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Current Usage', $metrics['memory']['current_usage_mb'] . ' MB'],
                ['Peak Usage', $metrics['memory']['peak_usage_mb'] . ' MB'],
                ['Memory Limit', $metrics['memory']['memory_limit']],
            ]
        );
    }
    
    private function displayPerformanceComparison(array $comparison): void
    {
        $this->displayCurrentMetrics($comparison['current']);
        
        if (isset($comparison['changes'])) {
            $this->newLine();
            $this->info('📈 Performance Changes:');
            
            $changes = [];
            
            if (isset($comparison['changes']['query_time_change'])) {
                $change = $comparison['changes']['query_time_change'];
                $symbol = $change > 0 ? '📈' : '📉';
                $changes[] = ['Query Time', $symbol . ' ' . round($change, 2) . ' ms'];
            }
            
            if (isset($comparison['changes']['memory_change'])) {
                $change = $comparison['changes']['memory_change'];
                $symbol = $change > 0 ? '📈' : '📉';
                $changes[] = ['Memory Usage', $symbol . ' ' . round($change, 2) . ' MB'];
            }
            
            if (isset($comparison['changes']['storage_change'])) {
                $change = $comparison['changes']['storage_change'];
                $symbol = $change > 0 ? '📈' : '📉';
                $changes[] = ['Storage Usage', $symbol . ' ' . round($change, 2) . ' MB'];
            }
            
            if (!empty($changes)) {
                $this->table(['Metric', 'Change'], $changes);
            }
        }
    }
}