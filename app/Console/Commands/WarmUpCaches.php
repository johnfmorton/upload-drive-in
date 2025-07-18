<?php

namespace App\Console\Commands;

use App\Services\FileMetadataCacheService;
use App\Services\ThumbnailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmUpCaches extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:warm-up 
                            {--files=100 : Number of recent files to cache}
                            {--thumbnails=50 : Number of recent images to generate thumbnails for}
                            {--clear : Clear existing caches before warming up}';

    /**
     * The console command description.
     */
    protected $description = 'Warm up file metadata and thumbnail caches for better performance';

    public function __construct(
        private FileMetadataCacheService $cacheService,
        private ThumbnailService $thumbnailService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting cache warm-up process...');
        
        $fileLimit = (int) $this->option('files');
        $thumbnailLimit = (int) $this->option('thumbnails');
        $shouldClear = $this->option('clear');
        
        if ($shouldClear) {
            $this->info('Clearing existing caches...');
            $this->clearCaches();
        }
        
        // Warm up file metadata cache
        $this->info("Warming up file metadata cache for {$fileLimit} recent files...");
        $metadataCached = $this->cacheService->warmUpCache($fileLimit);
        $this->info("✓ Cached metadata for {$metadataCached} files");
        
        // Warm up file statistics
        $this->info('Warming up file statistics...');
        $this->cacheService->getFileStatistics();
        $this->info('✓ File statistics cached');
        
        // Warm up filter options
        $this->info('Warming up filter options...');
        $this->cacheService->getFilterOptions();
        $this->info('✓ Filter options cached');
        
        // Warm up thumbnail cache
        $this->info("Warming up thumbnail cache for {$thumbnailLimit} recent images...");
        $thumbnailsCached = $this->thumbnailService->warmUpThumbnailCache($thumbnailLimit);
        $this->info("✓ Generated {$thumbnailsCached} thumbnails");
        
        // Display cache statistics
        $this->displayCacheStats();
        
        $this->info('Cache warm-up completed successfully!');
        
        return Command::SUCCESS;
    }
    
    private function clearCaches(): void
    {
        // Clear file metadata caches
        $this->cacheService->invalidateGlobalCaches();
        
        // Clear thumbnail caches
        $cleared = $this->thumbnailService->clearAllThumbnailCaches();
        $this->info("✓ Cleared {$cleared} thumbnail caches");
        
        // Clear general application cache
        Cache::flush();
        $this->info('✓ Cleared application cache');
    }
    
    private function displayCacheStats(): void
    {
        $this->newLine();
        $this->info('Cache Statistics:');
        
        // Check if caches are populated
        $hasStats = Cache::has('file_statistics');
        $hasFilters = Cache::has('file_filter_options');
        
        $this->table(
            ['Cache Type', 'Status', 'TTL'],
            [
                ['File Statistics', $hasStats ? '✓ Cached' : '✗ Not cached', '1 hour'],
                ['Filter Options', $hasFilters ? '✓ Cached' : '✗ Not cached', '1 hour'],
                ['File Metadata', 'Individual files', '1 hour'],
                ['Thumbnails', 'Individual images', '24 hours'],
            ]
        );
        
        // Memory usage if available
        if (function_exists('memory_get_usage')) {
            $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
            $this->info("Memory usage: {$memoryUsage} MB");
        }
    }
}