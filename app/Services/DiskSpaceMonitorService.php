<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DiskSpaceMonitorService
{
    /**
     * Minimum free space required in bytes (default: 2GB)
     */
    private int $minimumFreeSpace;

    /**
     * Warning threshold in bytes (default: 5GB)
     */
    private int $warningThreshold;

    public function __construct()
    {
        $this->minimumFreeSpace = config('upload.disk_space.minimum_free', 2 * 1024 * 1024 * 1024); // 2GB
        $this->warningThreshold = config('upload.disk_space.warning_threshold', 5 * 1024 * 1024 * 1024); // 5GB
    }

    /**
     * Check if there's enough disk space for an upload
     */
    public function hasEnoughSpaceForUpload(int $fileSize): bool
    {
        $freeSpace = $this->getFreeSpace();
        $requiredSpace = $fileSize + $this->minimumFreeSpace;
        
        return $freeSpace >= $requiredSpace;
    }

    /**
     * Get current free disk space in bytes
     */
    public function getFreeSpace(): int
    {
        return disk_free_space(storage_path());
    }

    /**
     * Get free space in human-readable format
     */
    public function getFreeSpaceFormatted(): string
    {
        return format_bytes($this->getFreeSpace());
    }

    /**
     * Check if disk space is in warning zone
     */
    public function isInWarningZone(): bool
    {
        return $this->getFreeSpace() < $this->warningThreshold;
    }

    /**
     * Check if disk space is critically low
     */
    public function isCriticallyLow(): bool
    {
        return $this->getFreeSpace() < $this->minimumFreeSpace;
    }

    /**
     * Get disk usage statistics
     */
    public function getDiskUsageStats(): array
    {
        $storagePath = storage_path();
        $totalSpace = disk_total_space($storagePath);
        $freeSpace = disk_free_space($storagePath);
        $usedSpace = $totalSpace - $freeSpace;

        return [
            'total_space' => $totalSpace,
            'free_space' => $freeSpace,
            'used_space' => $usedSpace,
            'usage_percentage' => round(($usedSpace / $totalSpace) * 100, 2),
            'total_space_formatted' => format_bytes($totalSpace),
            'free_space_formatted' => format_bytes($freeSpace),
            'used_space_formatted' => format_bytes($usedSpace),
        ];
    }

    /**
     * Get upload directory size
     */
    public function getUploadDirectorySize(): int
    {
        $disk = Storage::disk('public');
        $totalSize = 0;

        if (!$disk->exists('uploads')) {
            return 0;
        }

        $files = $disk->allFiles('uploads');
        foreach ($files as $file) {
            $totalSize += $disk->size($file);
        }

        return $totalSize;
    }

    /**
     * Emergency cleanup - remove oldest files if disk space is critically low
     */
    public function emergencyCleanup(): int
    {
        if (!$this->isCriticallyLow()) {
            return 0;
        }

        Log::warning('Disk space critically low, performing emergency cleanup');

        $disk = Storage::disk('public');
        $files = collect($disk->allFiles('uploads'))
            ->map(function ($file) use ($disk) {
                return [
                    'path' => $file,
                    'modified' => $disk->lastModified($file),
                    'size' => $disk->size($file)
                ];
            })
            ->sortBy('modified'); // Oldest first

        $deletedCount = 0;
        $freedSpace = 0;

        foreach ($files as $file) {
            if (!$this->isCriticallyLow()) {
                break;
            }

            $disk->delete($file['path']);
            $deletedCount++;
            $freedSpace += $file['size'];

            Log::info('Emergency cleanup deleted file', [
                'file' => $file['path'],
                'size' => format_bytes($file['size'])
            ]);
        }

        if ($deletedCount > 0) {
            Log::warning('Emergency cleanup completed', [
                'files_deleted' => $deletedCount,
                'space_freed' => format_bytes($freedSpace)
            ]);
        }

        return $deletedCount;
    }
}