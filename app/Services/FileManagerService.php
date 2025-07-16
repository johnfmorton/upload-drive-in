<?php

namespace App\Services;

use App\Models\FileUpload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service class for enhanced file management operations.
 * Handles business logic for file CRUD operations, filtering, and statistics.
 */
class FileManagerService
{
    /**
     * Get filtered and paginated files based on provided criteria.
     */
    public function getFilteredFiles(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = FileUpload::query()
            ->with(['clientUser', 'companyUser', 'uploadedByUser']);

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'pending') {
                $query->pending();
            } elseif ($filters['status'] === 'completed') {
                $query->completed();
            }
        }

        // Apply date range filter
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Apply user email filter
        if (!empty($filters['user_email'])) {
            $query->where('email', 'like', "%{$filters['user_email']}%");
        }

        // Apply file type filter
        if (!empty($filters['file_type'])) {
            $query->where('mime_type', 'like', "{$filters['file_type']}%");
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        $allowedSortFields = [
            'created_at', 'original_filename', 'file_size', 
            'email', 'mime_type', 'updated_at'
        ];
        
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get comprehensive file statistics for dashboard.
     */
    public function getFileStatistics(): array
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
            ->map(function ($item) {
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
            'file_types' => $fileTypes
        ];
    }

    /**
     * Get detailed information about a specific file.
     */
    public function getFileDetails(FileUpload $file): array
    {
        return [
            'file' => $file->load(['clientUser', 'companyUser', 'uploadedByUser']),
            'size_formatted' => $this->formatBytes($file->file_size),
            'upload_date_formatted' => $file->created_at->format('M j, Y g:i A'),
            'is_pending' => $file->isPending(),
            'has_local_copy' => $this->hasLocalFile($file),
            'google_drive_url' => $file->google_drive_file_id 
                ? "https://drive.google.com/file/d/{$file->google_drive_file_id}/view"
                : null,
            'file_extension' => pathinfo($file->original_filename, PATHINFO_EXTENSION),
            'mime_type_category' => $this->getMimeTypeCategory($file->mime_type)
        ];
    }

    /**
     * Update file metadata.
     */
    public function updateFileMetadata(FileUpload $file, array $data): FileUpload
    {
        $file->update(array_filter($data));
        
        Log::info('File metadata updated', [
            'file_id' => $file->id,
            'updated_fields' => array_keys(array_filter($data))
        ]);

        return $file->fresh();
    }

    /**
     * Delete a file from all storage locations and database.
     */
    public function deleteFile(FileUpload $file): bool
    {
        try {
            // Delete from Google Drive if exists
            if ($file->google_drive_file_id) {
                $deleted = $file->deleteFromGoogleDrive();
                if (!$deleted) {
                    Log::warning('Failed to delete file from Google Drive', [
                        'file_id' => $file->id,
                        'google_drive_file_id' => $file->google_drive_file_id
                    ]);
                }
            }

            // Delete local file if exists
            if ($this->hasLocalFile($file)) {
                Storage::disk('public')->delete('uploads/' . $file->filename);
            }

            // Delete database record
            $file->delete();

            Log::info('File deleted successfully', ['file_id' => $file->id]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete file', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Bulk delete multiple files.
     */
    public function bulkDeleteFiles(array $fileIds): int
    {
        $deletedCount = 0;
        $files = FileUpload::whereIn('id', $fileIds)->get();

        foreach ($files as $file) {
            try {
                $this->deleteFile($file);
                $deletedCount++;
            } catch (\Exception $e) {
                Log::error('Failed to delete file in bulk operation', [
                    'file_id' => $file->id,
                    'error' => $e->getMessage()
                ]);
                // Continue with other files
            }
        }

        Log::info('Bulk file deletion completed', [
            'requested_count' => count($fileIds),
            'deleted_count' => $deletedCount
        ]);

        return $deletedCount;
    }

    /**
     * Process pending uploads by queuing them for Google Drive upload.
     */
    public function processPendingUploads(): array
    {
        $pendingCount = FileUpload::pending()->count();

        if ($pendingCount === 0) {
            return [
                'count' => 0,
                'message' => 'No pending uploads found.'
            ];
        }

        // Call the artisan command to process pending uploads
        Artisan::call('uploads:process-pending', [
            '--limit' => 50
        ]);

        $output = Artisan::output();
        Log::info('Processed pending uploads via file manager', ['output' => $output]);

        return [
            'count' => $pendingCount,
            'message' => "Processing {$pendingCount} pending uploads. Check the queue status for progress."
        ];
    }

    /**
     * Download a file from local storage or Google Drive.
     */
    public function downloadFile(FileUpload $file): StreamedResponse
    {
        // Try local file first
        if ($this->hasLocalFile($file)) {
            return Storage::disk('public')->download(
                'uploads/' . $file->filename,
                $file->original_filename
            );
        }

        // If no local file and has Google Drive ID, redirect to Google Drive
        if ($file->google_drive_file_id) {
            throw new \Exception('File is stored in Google Drive. Please use the "View" button to access it.');
        }

        throw new \Exception('File not found in local storage or Google Drive.');
    }

    /**
     * Check if file exists in local storage.
     */
    private function hasLocalFile(FileUpload $file): bool
    {
        return Storage::disk('public')->exists('uploads/' . $file->filename);
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
}