<?php

namespace App\Services;

use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for caching file metadata to improve performance.
 */
class FileMetadataCacheService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'file_metadata:';
    private const STATS_CACHE_KEY = 'file_statistics';
    private const FILTER_OPTIONS_CACHE_KEY = 'file_filter_options';

    /**
     * Get cached file metadata or generate and cache it.
     */
    public function getFileMetadata(FileUpload $file): array
    {
        $cacheKey = self::CACHE_PREFIX . $file->id;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($file) {
            return $this->generateFileMetadata($file);
        });
    }

    /**
     * Get cached file statistics or generate and cache them.
     */
    public function getFileStatistics(): array
    {
        return Cache::remember(self::STATS_CACHE_KEY, self::CACHE_TTL, function () {
            return $this->generateFileStatistics();
        });
    }

    /**
     * Get cached filter options or generate and cache them.
     */
    public function getFilterOptions(): array
    {
        return Cache::remember(self::FILTER_OPTIONS_CACHE_KEY, self::CACHE_TTL, function () {
            return $this->generateFilterOptions();
        });
    }

    /**
     * Invalidate cache for a specific file.
     */
    public function invalidateFileCache(FileUpload $file): void
    {
        $cacheKey = self::CACHE_PREFIX . $file->id;
        Cache::forget($cacheKey);
        
        // Also invalidate global statistics and filter options
        $this->invalidateGlobalCaches();
        
        Log::debug('File metadata cache invalidated', ['file_id' => $file->id]);
    }

    /**
     * Invalidate global caches (statistics and filter options).
     */
    public function invalidateGlobalCaches(): void
    {
        Cache::forget(self::STATS_CACHE_KEY);
        Cache::forget(self::FILTER_OPTIONS_CACHE_KEY);
        
        Log::debug('Global file caches invalidated');
    }

    /**
     * Warm up cache for recently accessed files.
     */
    public function warmUpCache(int $limit = 100): int
    {
        $recentFiles = FileUpload::orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();

        $warmedCount = 0;
        foreach ($recentFiles as $file) {
            $this->getFileMetadata($file);
            $warmedCount++;
        }

        Log::info('File metadata cache warmed up', ['files_cached' => $warmedCount]);
        
        return $warmedCount;
    }

    /**
     * Generate file metadata for caching.
     */
    private function generateFileMetadata(FileUpload $file): array
    {
        return [
            'id' => $file->id,
            'original_filename' => $file->original_filename,
            'file_size' => $file->file_size,
            'file_size_human' => $this->formatBytes($file->file_size),
            'mime_type' => $file->mime_type,
            'mime_type_category' => $this->getMimeTypeCategory($file->mime_type),
            'created_at' => $file->created_at->toISOString(),
            'updated_at' => $file->updated_at->toISOString(),
            'is_pending' => $file->isPending(),
            'can_preview' => $file->isPreviewable(),
            'file_extension' => pathinfo($file->original_filename, PATHINFO_EXTENSION),
            'google_drive_url' => $file->google_drive_file_id 
                ? "https://drive.google.com/file/d/{$file->google_drive_file_id}/view"
                : null,
        ];
    }

    /**
     * Generate file statistics for caching.
     */
    private function generateFileStatistics(): array
    {
        $totalFiles = FileUpload::count();
        $pendingFiles = FileUpload::pending()->count();
        $completedFiles = FileUpload::completed()->count();
        $totalSize = FileUpload::sum('file_size');
        
        // Get files uploaded today
        $todayFiles = FileUpload::whereDate('created_at', today())->count();
        
        // Get files uploaded this week
        $weekFiles = FileUpload::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();
        
        // Get files uploaded this month
        $monthFiles = FileUpload::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Get most common file types
        $fileTypes = FileUpload::selectRaw('mime_type, COUNT(*) as count')
            ->groupBy('mime_type')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($item) use ($totalFiles) {
                return [
                    'type' => $item->mime_type,
                    'count' => $item->count,
                    'percentage' => $totalFiles > 0 ? round(($item->count / $totalFiles) * 100, 1) : 0
                ];
            });

        return [
            'total_files' => $totalFiles,
            'pending_files' => $pendingFiles,
            'completed_files' => $completedFiles,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'today_files' => $todayFiles,
            'week_files' => $weekFiles,
            'month_files' => $monthFiles,
            'completion_rate' => $totalFiles > 0 ? round(($completedFiles / $totalFiles) * 100, 1) : 0,
            'file_types' => $fileTypes,
            'cached_at' => now()->toISOString()
        ];
    }

    /**
     * Generate filter options for caching.
     */
    private function generateFilterOptions(): array
    {
        // Get unique file types with counts
        $fileTypes = FileUpload::selectRaw('mime_type, COUNT(*) as count')
            ->groupBy('mime_type')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->mime_type,
                    'label' => $this->getMimeTypeLabel($item->mime_type),
                    'count' => $item->count,
                    'category' => $this->getMimeTypeCategory($item->mime_type)
                ];
            });

        // Get unique user emails with counts
        $userEmails = FileUpload::selectRaw('email, COUNT(*) as count')
            ->whereNotNull('email')
            ->groupBy('email')
            ->orderByDesc('count')
            ->limit(50) // Limit to prevent huge lists
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->email,
                    'label' => $item->email,
                    'count' => $item->count
                ];
            });

        // Get file size ranges
        $fileSizeRanges = [
            ['label' => 'Small (< 1MB)', 'min' => 0, 'max' => 1024 * 1024],
            ['label' => 'Medium (1MB - 10MB)', 'min' => 1024 * 1024, 'max' => 10 * 1024 * 1024],
            ['label' => 'Large (10MB - 100MB)', 'min' => 10 * 1024 * 1024, 'max' => 100 * 1024 * 1024],
            ['label' => 'Very Large (> 100MB)', 'min' => 100 * 1024 * 1024, 'max' => null],
        ];

        return [
            'file_types' => $fileTypes,
            'user_emails' => $userEmails,
            'file_size_ranges' => $fileSizeRanges,
            'cached_at' => now()->toISOString()
        ];
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 Bytes';
        }

        $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $index = floor(log($bytes, 1024));
        
        return round($bytes / pow(1024, $index), 2) . ' ' . $units[$index];
    }

    /**
     * Get mime type category for better organization.
     */
    private function getMimeTypeCategory(string $mimeType): string
    {
        $categories = [
            'image' => ['image/'],
            'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument'],
            'video' => ['video/'],
            'audio' => ['audio/'],
            'archive' => ['application/zip', 'application/x-rar', 'application/x-7z'],
            'text' => ['text/']
        ];

        foreach ($categories as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_starts_with($mimeType, $pattern)) {
                    return $category;
                }
            }
        }

        return 'other';
    }

    /**
     * Get human-readable MIME type label.
     */
    private function getMimeTypeLabel(string $mimeType): string
    {
        $labels = [
            'image/jpeg' => 'JPEG Image',
            'image/png' => 'PNG Image',
            'image/gif' => 'GIF Image',
            'image/webp' => 'WebP Image',
            'application/pdf' => 'PDF Document',
            'application/msword' => 'Word Document',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word Document (DOCX)',
            'application/vnd.ms-excel' => 'Excel Spreadsheet',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel Spreadsheet (XLSX)',
            'text/plain' => 'Text File',
            'text/html' => 'HTML File',
            'application/zip' => 'ZIP Archive',
            'video/mp4' => 'MP4 Video',
            'audio/mpeg' => 'MP3 Audio',
        ];

        return $labels[$mimeType] ?? ucfirst(str_replace(['/', '-'], [' ', ' '], $mimeType));
    }
}