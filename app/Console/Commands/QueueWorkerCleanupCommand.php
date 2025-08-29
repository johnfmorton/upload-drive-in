<?php

namespace App\Console\Commands;

use App\Services\QueueWorkerPerformanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Console command for performing queue worker performance cleanup.
 * 
 * This command handles automatic cleanup of old test jobs, cache entries,
 * and performance optimization tasks for the queue worker status system.
 */
class QueueWorkerCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue-worker:cleanup 
                            {--force : Force cleanup even if recently performed}
                            {--stats : Show performance statistics}
                            {--dry-run : Show what would be cleaned without actually cleaning}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old queue worker test data and optimize cache performance';

    /**
     * The performance service instance.
     */
    private QueueWorkerPerformanceService $performanceService;

    /**
     * Create a new command instance.
     */
    public function __construct(QueueWorkerPerformanceService $performanceService)
    {
        parent::__construct();
        $this->performanceService = $performanceService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Queue Worker Performance Cleanup');
        $this->info('=====================================');

        try {
            // Show performance statistics if requested
            if ($this->option('stats')) {
                $this->showPerformanceStats();
                return Command::SUCCESS;
            }

            // Perform dry run if requested
            if ($this->option('dry-run')) {
                $this->performDryRun();
                return Command::SUCCESS;
            }

            // Perform actual cleanup
            $this->performCleanup();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Cleanup failed: ' . $e->getMessage());
            Log::error('Queue worker cleanup command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Show performance statistics.
     */
    private function showPerformanceStats(): void
    {
        $this->info('Retrieving performance statistics...');

        $stats = $this->performanceService->getCachePerformanceStats();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Cache Driver', $stats['cache_driver'] ?? 'Unknown'],
                ['Job Index Size', $stats['job_index_size'] ?? 0],
                ['Estimated Cache Usage', $this->formatBytes($stats['estimated_cache_usage'] ?? 0)],
                ['Timestamp', $stats['timestamp'] ?? 'Unknown'],
            ]
        );

        if (!empty($stats['cleanup_recommendations'])) {
            $this->warn('Recommendations:');
            foreach ($stats['cleanup_recommendations'] as $recommendation) {
                $this->line('  • ' . $recommendation);
            }
        } else {
            $this->info('✓ No cleanup recommendations at this time');
        }

        if (isset($stats['error'])) {
            $this->error('Error retrieving stats: ' . $stats['error']);
        }
    }

    /**
     * Perform a dry run to show what would be cleaned.
     */
    private function performDryRun(): void
    {
        $this->info('Performing dry run (no actual cleanup will be performed)...');

        // Get current statistics
        $stats = $this->performanceService->getCachePerformanceStats();

        $this->table(
            ['Item', 'Current Count', 'Action'],
            [
                ['Job Index Entries', $stats['job_index_size'] ?? 0, 'Would optimize and trim'],
                ['Cache Entries', 'Unknown', 'Would clean expired entries'],
                ['Old Test Jobs', 'Unknown', 'Would remove jobs older than 24 hours'],
                ['Metrics Data', 'Unknown', 'Would clean old metrics'],
            ]
        );

        $this->info('Use --force flag to perform actual cleanup');
    }

    /**
     * Perform the actual cleanup.
     */
    private function performCleanup(): void
    {
        $force = $this->option('force');

        $this->info('Starting cleanup process...');

        if ($force) {
            $this->warn('Force flag enabled - bypassing recent cleanup checks');
        }

        // Show progress bar
        $progressBar = $this->output->createProgressBar(4);
        $progressBar->setFormat('verbose');

        $progressBar->start();

        // Perform comprehensive cleanup
        $progressBar->setMessage('Performing comprehensive cleanup...');
        $cleanupStats = $this->performanceService->performComprehensiveCleanup($force);
        $progressBar->advance();

        $progressBar->setMessage('Warming up cache...');
        $this->performanceService->warmUpCache();
        $progressBar->advance();

        $progressBar->setMessage('Generating performance report...');
        $newStats = $this->performanceService->getCachePerformanceStats();
        $progressBar->advance();

        $progressBar->setMessage('Cleanup completed');
        $progressBar->finish();

        $this->newLine(2);

        // Display cleanup results
        $this->displayCleanupResults($cleanupStats, $newStats);
    }

    /**
     * Display cleanup results.
     */
    private function displayCleanupResults(array $cleanupStats, array $newStats): void
    {
        if (isset($cleanupStats['skipped']) && $cleanupStats['skipped']) {
            $this->warn('Cleanup was skipped: ' . $cleanupStats['reason']);
            $this->info('Use --force flag to override');
            return;
        }

        $this->info('Cleanup Results:');
        $this->info('===============');

        $results = [
            ['Test Jobs Cleaned', $cleanupStats['test_jobs_cleaned'] ?? 0],
            ['Cache Entries Cleaned', $cleanupStats['cache_entries_cleaned'] ?? 0],
            ['Index Entries Cleaned', $cleanupStats['index_entries_cleaned'] ?? 0],
            ['Metrics Cleaned', $cleanupStats['metrics_cleaned'] ?? 0],
        ];

        if (isset($cleanupStats['duration_ms'])) {
            $results[] = ['Duration', round($cleanupStats['duration_ms']) . 'ms'];
        }

        $this->table(['Item', 'Count'], $results);

        // Show any errors
        if (!empty($cleanupStats['errors'])) {
            $this->error('Errors encountered:');
            foreach ($cleanupStats['errors'] as $error) {
                $this->line('  • ' . $error);
            }
        }

        // Show current statistics
        $this->info('Current Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Job Index Size', $newStats['job_index_size'] ?? 0],
                ['Estimated Cache Usage', $this->formatBytes($newStats['estimated_cache_usage'] ?? 0)],
            ]
        );

        // Show recommendations
        if (!empty($newStats['cleanup_recommendations'])) {
            $this->warn('Recommendations:');
            foreach ($newStats['cleanup_recommendations'] as $recommendation) {
                $this->line('  • ' . $recommendation);
            }
        } else {
            $this->info('✓ System is optimally configured');
        }

        $this->info('Cleanup completed successfully!');
    }

    /**
     * Format bytes into human-readable format.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}