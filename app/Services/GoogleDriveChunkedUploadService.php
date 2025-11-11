<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Http\MediaFileUpload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Service for handling chunked/resumable uploads to Google Drive
 * 
 * This service handles large file uploads by breaking them into chunks
 * to avoid memory exhaustion issues.
 */
class GoogleDriveChunkedUploadService
{
    private function getConfig(string $key, $default = null)
    {
        return config("cloud-storage.providers.google-drive.chunked_upload.{$key}", $default);
    }

    private function getDefaultChunkSize(): int
    {
        return $this->getConfig('default_chunk_size', 8 * 1024 * 1024);
    }

    private function getMinChunkSize(): int
    {
        return $this->getConfig('min_chunk_size', 256 * 1024);
    }

    private function getMaxChunkSize(): int
    {
        return $this->getConfig('max_chunk_size', 100 * 1024 * 1024);
    }

    public function __construct(
        private readonly GoogleDriveService $driveService
    ) {}

    /**
     * Upload a large file using chunked/resumable upload
     *
     * @param User $user The user whose Google Drive to use
     * @param string $localPath Local file path relative to storage/app/public
     * @param string $targetFolderId Google Drive folder ID to upload to
     * @param string $filename Target filename in Google Drive
     * @param string $mimeType File MIME type
     * @param string|null $description File description
     * @param int|null $chunkSize Custom chunk size in bytes
     * @return string Google Drive file ID
     * @throws Exception
     */
    public function uploadFileChunked(
        User $user,
        string $localPath,
        string $targetFolderId,
        string $filename,
        string $mimeType,
        ?string $description = null,
        ?int $chunkSize = null
    ): string {
        $startTime = microtime(true);
        $fullPath = Storage::disk('public')->path($localPath);
        
        if (!file_exists($fullPath)) {
            throw new Exception(__('messages.chunked_upload_local_file_not_found', ['path' => $localPath]));
        }

        $fileSize = filesize($fullPath);
        $chunkSize = $this->determineOptimalChunkSize($fileSize, $chunkSize);

        Log::info('Starting chunked upload to Google Drive', [
            'user_id' => $user->id,
            'file_path' => $localPath,
            'file_size' => $fileSize,
            'chunk_size' => $chunkSize,
            'target_folder_id' => $targetFolderId,
            'filename' => $filename
        ]);

        try {
            $driveService = $this->driveService->getDriveService($user);
            $client = $driveService->getClient();

            // Prepare file metadata
            $fileMetadata = new DriveFile([
                'name' => $filename,
                'parents' => [$targetFolderId]
            ]);

            if ($description) {
                $fileMetadata->setDescription($description);
            }

            // Open file handle for reading
            $fileHandle = fopen($fullPath, 'rb');
            if (!$fileHandle) {
                throw new Exception(__('messages.chunked_upload_could_not_open_file', ['path' => $localPath]));
            }

            try {
                // Enable deferred mode to get the HTTP request object
                $client->setDefer(true);
                $httpRequest = $driveService->files->create($fileMetadata);
                $client->setDefer(false);
                
                // Create media upload object for resumable upload
                $media = new MediaFileUpload(
                    $client,
                    $httpRequest,
                    $mimeType,
                    null,
                    true,
                    $chunkSize
                );

                $media->setFileSize($fileSize);

                // Upload file in chunks
                $status = false;
                $uploadedBytes = 0;
                $chunkNumber = 1;

                while (!$status && !feof($fileHandle)) {
                    $chunkStartTime = microtime(true);
                    
                    // Read chunk from file
                    $chunk = fread($fileHandle, $chunkSize);
                    if ($chunk === false) {
                        throw new Exception(__('messages.chunked_upload_failed_to_read_chunk'));
                    }

                    $chunkActualSize = strlen($chunk);
                    if ($chunkActualSize === 0) {
                        break; // End of file
                    }

                    // Upload chunk
                    $status = $media->nextChunk($chunk);
                    $uploadedBytes += $chunkActualSize;
                    
                    $chunkDuration = (microtime(true) - $chunkStartTime) * 1000;
                    $progressPercent = ($uploadedBytes / $fileSize) * 100;

                    Log::debug('Uploaded chunk to Google Drive', [
                        'user_id' => $user->id,
                        'chunk_number' => $chunkNumber,
                        'chunk_size' => $chunkActualSize,
                        'uploaded_bytes' => $uploadedBytes,
                        'total_bytes' => $fileSize,
                        'progress_percent' => round($progressPercent, 2),
                        'chunk_duration_ms' => round($chunkDuration, 2),
                        'filename' => $filename
                    ]);

                    $chunkNumber++;

                    // Optional: Add small delay to prevent overwhelming the API
                    $chunkDelayMs = $this->getConfig('chunk_delay_ms', 100);
                    if ($chunkDelayMs > 0 && $chunkNumber % 10 === 0) {
                        usleep($chunkDelayMs * 1000); // Convert ms to microseconds
                    }
                }

                if (!$status) {
                    throw new Exception(__('messages.chunked_upload_no_file_object_returned'));
                }

                $fileId = $status->getId();
                $totalDuration = (microtime(true) - $startTime) * 1000;

                Log::info('Chunked upload to Google Drive completed successfully', [
                    'user_id' => $user->id,
                    'file_id' => $fileId,
                    'filename' => $filename,
                    'file_size' => $fileSize,
                    'total_chunks' => $chunkNumber - 1,
                    'chunk_size' => $chunkSize,
                    'total_duration_ms' => round($totalDuration, 2),
                    'average_speed_mbps' => round(($fileSize / 1024 / 1024) / ($totalDuration / 1000), 2)
                ]);

                return $fileId;

            } finally {
                fclose($fileHandle);
            }

        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::error('Chunked upload to Google Drive failed', [
                'user_id' => $user->id,
                'file_path' => $localPath,
                'filename' => $filename,
                'file_size' => $fileSize,
                'chunk_size' => $chunkSize,
                'error' => $e->getMessage(),
                'duration_ms' => round($duration, 2)
            ]);

            throw $e;
        }
    }

    /**
     * Determine optimal chunk size based on file size and available memory
     *
     * @param int $fileSize File size in bytes
     * @param int|null $customChunkSize Custom chunk size override
     * @return int Optimal chunk size in bytes
     */
    private function determineOptimalChunkSize(int $fileSize, ?int $customChunkSize = null): int
    {
        $minChunkSize = $this->getMinChunkSize();
        $maxChunkSize = $this->getMaxChunkSize();
        
        if ($customChunkSize !== null) {
            return max($minChunkSize, min($maxChunkSize, $customChunkSize));
        }

        // Determine chunk size based on file size and available memory
        $memoryLimit = $this->getMemoryLimitBytes();
        $availableMemory = $memoryLimit - memory_get_usage(true);
        
        // Use a conservative approach - chunk size should be much smaller than available memory
        $maxSafeChunkSize = max($minChunkSize, intval($availableMemory * 0.1)); // 10% of available memory

        // Choose chunk size based on file size
        $optimalChunkSize = match (true) {
            $fileSize < 10 * 1024 * 1024 => 1 * 1024 * 1024,      // 1MB for files < 10MB
            $fileSize < 100 * 1024 * 1024 => 4 * 1024 * 1024,     // 4MB for files < 100MB
            $fileSize < 500 * 1024 * 1024 => 8 * 1024 * 1024,     // 8MB for files < 500MB
            $fileSize < 1024 * 1024 * 1024 => 16 * 1024 * 1024,   // 16MB for files < 1GB
            default => 32 * 1024 * 1024                            // 32MB for files >= 1GB
        };

        // Ensure chunk size doesn't exceed safe memory limits
        $finalChunkSize = min($optimalChunkSize, $maxSafeChunkSize, $maxChunkSize);
        $finalChunkSize = max($finalChunkSize, $minChunkSize);

        Log::debug('Determined optimal chunk size', [
            'file_size' => $fileSize,
            'memory_limit' => $memoryLimit,
            'available_memory' => $availableMemory,
            'max_safe_chunk_size' => $maxSafeChunkSize,
            'optimal_chunk_size' => $optimalChunkSize,
            'final_chunk_size' => $finalChunkSize
        ]);

        return $finalChunkSize;
    }

    /**
     * Get PHP memory limit in bytes
     *
     * @return int Memory limit in bytes
     */
    private function getMemoryLimitBytes(): int
    {
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit === '-1') {
            return PHP_INT_MAX; // No limit
        }

        $value = intval($memoryLimit);
        $unit = strtoupper(substr($memoryLimit, -1));

        return match ($unit) {
            'G' => $value * 1024 * 1024 * 1024,
            'M' => $value * 1024 * 1024,
            'K' => $value * 1024,
            default => $value
        };
    }

    /**
     * Check if a file should use chunked upload based on size and memory constraints
     *
     * @param int $fileSize File size in bytes
     * @param int $memoryThreshold Memory threshold percentage (default 25%)
     * @return bool True if chunked upload should be used
     */
    public function shouldUseChunkedUpload(int $fileSize, ?int $memoryThreshold = null): bool
    {
        if (!$this->getConfig('enabled', true)) {
            return false;
        }

        $memoryThreshold = $memoryThreshold ?? $this->getConfig('memory_threshold_percent', 25);
        $thresholdBytes = $this->getConfig('threshold_bytes', 52428800); // 50MB default
        
        $memoryLimit = $this->getMemoryLimitBytes();
        $currentMemoryUsage = memory_get_usage(true);
        $availableMemory = $memoryLimit - $currentMemoryUsage;
        
        // Use chunked upload if file size exceeds threshold percentage of available memory
        $memoryThresholdBytes = intval($availableMemory * ($memoryThreshold / 100));
        
        $shouldUseChunked = $fileSize > $memoryThresholdBytes || $fileSize > $thresholdBytes;

        Log::debug('Chunked upload decision', [
            'file_size' => $fileSize,
            'memory_limit' => $memoryLimit,
            'current_memory_usage' => $currentMemoryUsage,
            'available_memory' => $availableMemory,
            'memory_threshold_percent' => $memoryThreshold,
            'memory_threshold_bytes' => $memoryThresholdBytes,
            'should_use_chunked' => $shouldUseChunked
        ]);

        return $shouldUseChunked;
    }

    /**
     * Get upload progress information for monitoring
     *
     * @param int $uploadedBytes Bytes uploaded so far
     * @param int $totalBytes Total file size
     * @param float $startTime Upload start time (microtime)
     * @return array Progress information
     */
    public function getUploadProgress(int $uploadedBytes, int $totalBytes, float $startTime): array
    {
        $currentTime = microtime(true);
        $elapsedTime = $currentTime - $startTime;
        $progressPercent = ($uploadedBytes / $totalBytes) * 100;
        
        $uploadSpeed = $elapsedTime > 0 ? $uploadedBytes / $elapsedTime : 0; // bytes per second
        $remainingBytes = $totalBytes - $uploadedBytes;
        $estimatedTimeRemaining = $uploadSpeed > 0 ? $remainingBytes / $uploadSpeed : 0;

        return [
            'uploaded_bytes' => $uploadedBytes,
            'total_bytes' => $totalBytes,
            'progress_percent' => round($progressPercent, 2),
            'elapsed_time_seconds' => round($elapsedTime, 2),
            'upload_speed_mbps' => round(($uploadSpeed / 1024 / 1024), 2),
            'estimated_time_remaining_seconds' => round($estimatedTimeRemaining, 2),
            'estimated_completion_time' => date('Y-m-d H:i:s', time() + intval($estimatedTimeRemaining))
        ];
    }
}