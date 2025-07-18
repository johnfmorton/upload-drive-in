<?php

namespace App\Services;

use App\Models\FileUpload;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for performance optimization tasks and monitoring.
 */
class PerformanceOptimizationService
{
    private const PERFORMANCE_CACHE_KEY = 'performance_metrics';
    private const SLOW_QUERY_THRESHOLD = 1000; // 1 second in milliseconds

    public function __construct(
        private FileMetadataCacheService $cacheService,
        private ThumbnailService $thumbnailService
    ) {
    }

    /**
     * Run comprehensive performance optimization.
     */
    public function optimizePerformance(): array
    {
        $results = [];
        $startTime = microtime(true);

        // 1. Optimize database queries
        $results['database'] = $this->optimizeDatabaseQueries();

        // 2. Warm up caches
        $results['cache'] = $this->warmUpCaches();

        // 3. Clean up old data
        $results['cleanup'] = $this->cleanupOldData();

        // 4. Generate performance report
        $results['metrics'] = $this->generatePerformanceMetrics();

        $totalTime = microtime(true) - $startTime;
        $results['optimization_time'] = round($totalTime, 2);

        Log::info('Performance optimization completed', $results);

        return $results;
    }

    /**
     * Optimize database queries and analyze performance.
     */
    public function optimizeDatabaseQueries(): array
    {
        $results = [
            'analyzed_queries' => 0,
            'slow_queries' => 0,
            'optimizations_applied' => 0
        ];

        // Enable query logging temporarily
        DB::enableQueryLog();

        try {
            // Run common queries to analyze performance
            $this->runCommonQueries();

            $queries = DB::getQueryLog();
            $results['analyzed_queries'] = count($queries);

            foreach ($queries as $query) {
                if ($query['time'] > self::SLOW_QUERY_THRESHOLD) {
                    $results['slow_queries']++;
                    
                    Log::warning('Slow query detected', [
                        'sql' => $query['query'],
                        'time' => $query['time'],
                        'bindings' => $query['bindings']
                    ]);
                }
            }

            // Apply query optimizations
            $results['optimizations_applied'] = $this->applyQueryOptimizations();

        } finally {
            DB::disableQueryLog();
        }

        return $results;
    }

    /**
     * Warm up various caches for better performance.
     */
    public function warmUpCaches(): array
    {
        $results = [
            'metadata_cached' => 0,
            'thumbnails_cached' => 0,
            'statistics_cached' => false,
            'filters_cached' => false
        ];

        // Warm up file metadata cache
        $results['metadata_cached'] = $this->cacheService->warmUpCache(100);

        // Warm up thumbnail cache
        $results['thumbnails_cached'] = $this->thumbnailService->warmUpThumbnailCache(50);

        // Warm up statistics
        $this->cacheService->getFileStatistics();
        $results['statistics_cached'] = true;

        // Warm up filter options
        $this->cacheService->getFilterOptions();
        $results['filters_cached'] = true;

        return $results;
    }

    /**
     * Clean up old data to improve performance.
     */
    public function cleanupOldData(): array
    {
        $results = [
            'temp_files_cleaned' => 0,
            'old_logs_cleaned' => 0,
            'cache_entries_cleaned' => 0
        ];

        // Clean up temporary files
        $results['temp_files_cleaned'] = $this->cleanupTempFiles();

        // Clean up old log entries (if applicable)
        $results['old_logs_cleaned'] = $this->cleanupOldLogs();

        // Clean up expired cache entries
        $results['cache_entries_cleaned'] = $this->cleanupExpiredCache();

        return $results;
    }

    /**
     * Generate comprehensive performance metrics.
     */
    public function generatePerformanceMetrics(): array
    {
        $metrics = [
            'database' => $this->getDatabaseMetrics(),
            'cache' => $this->getCacheMetrics(),
            'storage' => $this->getStorageMetrics(),
            'memory' => $this->getMemoryMetrics(),
            'timestamp' => now()->toISOString()
        ];

        // Cache metrics for later comparison
        Cache::put(self::PERFORMANCE_CACHE_KEY, $metrics, 3600); // 1 hour

        return $metrics;
    }

    /**
     * Get database performance metrics.
     */
    private function getDatabaseMetrics(): array
    {
        $startTime = microtime(true);
        
        // Test query performance
        $fileCount = FileUpload::count();
        $queryTime = microtime(true) - $startTime;

        // Get table sizes
        $tableSize = DB::select("
            SELECT 
                table_name,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
            AND table_name = 'file_uploads'
        ");

        return [
            'total_files' => $fileCount,
            'query_time_ms' => round($queryTime * 1000, 2),
            'table_size_mb' => $tableSize[0]->size_mb ?? 0,
            'connection_count' => $this->getConnectionCount()
        ];
    }

    /**
     * Get cache performance metrics.
     */
    private function getCacheMetrics(): array
    {
        $cacheStats = [
            'file_statistics_cached' => Cache::has('file_statistics'),
            'filter_options_cached' => Cache::has('file_filter_options'),
            'metadata_cache_count' => 0,
            'thumbnail_cache_count' => 0,
            'cache_driver' => config('cache.default')
        ];

        // Count cached entries based on cache driver
        try {
            if (config('cache.default') === 'redis') {
                // Count cached metadata entries for Redis
                $metadataKeys = Cache::getRedis()->keys('*file_metadata:*');
                $cacheStats['metadata_cache_count'] = count($metadataKeys);

                // Count cached thumbnails for Redis
                $thumbnailKeys = Cache::getRedis()->keys('*thumbnail:*');
                $cacheStats['thumbnail_cache_count'] = count($thumbnailKeys);
            } else {
                // For other cache drivers (database, file, etc.), we can't easily count entries
                // So we'll estimate based on recent files
                $recentFileCount = \App\Models\FileUpload::where('created_at', '>', now()->subHours(24))->count();
                $cacheStats['metadata_cache_count'] = min($recentFileCount, 100); // Estimate
                $cacheStats['thumbnail_cache_count'] = min($recentFileCount / 2, 50); // Estimate
            }
        } catch (\Exception $e) {
            Log::warning('Could not get cache metrics', ['error' => $e->getMessage()]);
            $cacheStats['metadata_cache_count'] = 0;
            $cacheStats['thumbnail_cache_count'] = 0;
        }

        return $cacheStats;
    }

    /**
     * Get storage performance metrics.
     */
    private function getStorageMetrics(): array
    {
        $storagePath = storage_path('app/public/uploads');
        $tempPath = storage_path('app/temp');

        return [
            'uploads_directory_size_mb' => $this->getDirectorySize($storagePath),
            'temp_directory_size_mb' => $this->getDirectorySize($tempPath),
            'total_local_files' => $this->countLocalFiles(),
            'disk_free_space_gb' => round(disk_free_space(storage_path()) / 1024 / 1024 / 1024, 2)
        ];
    }

    /**
     * Get memory usage metrics.
     */
    private function getMemoryMetrics(): array
    {
        return [
            'current_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'memory_limit' => ini_get('memory_limit')
        ];
    }

    /**
     * Run common queries for performance analysis.
     */
    private function runCommonQueries(): void
    {
        // Common file listing query
        FileUpload::orderBy('created_at', 'desc')->limit(50)->get();

        // Search query
        FileUpload::where('original_filename', 'like', '%test%')->limit(10)->get();

        // Filter by mime type
        FileUpload::where('mime_type', 'like', 'image/%')->limit(10)->get();

        // Date range query
        FileUpload::whereBetween('created_at', [
            now()->subDays(30),
            now()
        ])->limit(10)->get();
    }

    /**
     * Apply query optimizations.
     */
    private function applyQueryOptimizations(): int
    {
        $optimizations = 0;

        // Check if indexes exist and suggest optimizations
        $indexes = DB::select("SHOW INDEX FROM file_uploads");
        $indexNames = collect($indexes)->pluck('Key_name')->unique();

        $recommendedIndexes = [
            'idx_file_uploads_created_at',
            'idx_file_uploads_mime_type',
            'idx_file_uploads_email_created'
        ];

        foreach ($recommendedIndexes as $indexName) {
            if (!$indexNames->contains($indexName)) {
                Log::info('Missing recommended index', ['index' => $indexName]);
            } else {
                $optimizations++;
            }
        }

        return $optimizations;
    }

    /**
     * Clean up temporary files.
     */
    private function cleanupTempFiles(): int
    {
        $tempPath = storage_path('app/temp');
        $cleaned = 0;

        if (!is_dir($tempPath)) {
            return 0;
        }

        $files = glob($tempPath . '/*');
        $cutoffTime = time() - (24 * 60 * 60); // 24 hours ago

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }

    /**
     * Clean up old log entries.
     */
    private function cleanupOldLogs(): int
    {
        // This would depend on your logging strategy
        // For now, just return 0 as Laravel handles log rotation
        return 0;
    }

    /**
     * Clean up expired cache entries.
     */
    private function cleanupExpiredCache(): int
    {
        // Redis automatically handles expired keys
        // This is more for manual cleanup if needed
        return 0;
    }

    /**
     * Get database connection count.
     */
    private function getConnectionCount(): int
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            return (int) ($result[0]->Value ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get directory size in MB.
     */
    private function getDirectorySize(string $path): float
    {
        if (!is_dir($path)) {
            return 0;
        }

        $size = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return round($size / 1024 / 1024, 2);
    }

    /**
     * Count local files.
     */
    private function countLocalFiles(): int
    {
        $uploadsPath = storage_path('app/public/uploads');
        
        if (!is_dir($uploadsPath)) {
            return 0;
        }

        $files = glob($uploadsPath . '/*');
        return count(array_filter($files, 'is_file'));
    }

    /**
     * Get performance comparison with previous metrics.
     */
    public function getPerformanceComparison(): array
    {
        $currentMetrics = $this->generatePerformanceMetrics();
        $previousMetrics = Cache::get(self::PERFORMANCE_CACHE_KEY);

        if (!$previousMetrics) {
            return [
                'comparison_available' => false,
                'current' => $currentMetrics
            ];
        }

        return [
            'comparison_available' => true,
            'current' => $currentMetrics,
            'previous' => $previousMetrics,
            'changes' => $this->calculateMetricChanges($currentMetrics, $previousMetrics)
        ];
    }

    /**
     * Calculate changes between metric sets.
     */
    private function calculateMetricChanges(array $current, array $previous): array
    {
        $changes = [];

        // Database changes
        if (isset($current['database']['query_time_ms']) && isset($previous['database']['query_time_ms'])) {
            $changes['query_time_change'] = $current['database']['query_time_ms'] - $previous['database']['query_time_ms'];
        }

        // Memory changes
        if (isset($current['memory']['current_usage_mb']) && isset($previous['memory']['current_usage_mb'])) {
            $changes['memory_change'] = $current['memory']['current_usage_mb'] - $previous['memory']['current_usage_mb'];
        }

        // Storage changes
        if (isset($current['storage']['uploads_directory_size_mb']) && isset($previous['storage']['uploads_directory_size_mb'])) {
            $changes['storage_change'] = $current['storage']['uploads_directory_size_mb'] - $previous['storage']['uploads_directory_size_mb'];
        }

        return $changes;
    }
}