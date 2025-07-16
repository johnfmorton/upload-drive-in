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
     * Create and download a ZIP archive containing multiple files.
     */
    public function bulkDownloadFiles(array $fileIds): StreamedResponse
    {
        $files = FileUpload::whereIn('id', $fileIds)->get();
        
        if ($files->isEmpty()) {
            throw new \Exception('No files found for download.');
        }

        $zipFileName = 'bulk_download_' . now()->format('Y-m-d_H-i-s') . '.zip';
        $tempZipPath = storage_path('app/temp/' . $zipFileName);
        
        // Ensure temp directory exists
        if (!file_exists(dirname($tempZipPath))) {
            mkdir(dirname($tempZipPath), 0755, true);
        }

        try {
            $zip = new \ZipArchive();
            $result = $zip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            
            if ($result !== TRUE) {
                throw new \Exception('Cannot create ZIP archive: ' . $this->getZipError($result));
            }

            $addedFiles = 0;
            $skippedFiles = [];

            foreach ($files as $file) {
                try {
                    $fileContent = $this->getFileContent($file);
                    
                    if ($fileContent !== null) {
                        // Ensure unique filename in ZIP by adding ID if needed
                        $zipEntryName = $this->getUniqueZipEntryName($zip, $file->original_filename, $file->id);
                        $zip->addFromString($zipEntryName, $fileContent);
                        $addedFiles++;
                        
                        Log::info('Added file to ZIP archive', [
                            'file_id' => $file->id,
                            'original_filename' => $file->original_filename,
                            'zip_entry_name' => $zipEntryName
                        ]);
                    } else {
                        $skippedFiles[] = $file->original_filename;
                        Log::warning('Skipped file in bulk download - not accessible', [
                            'file_id' => $file->id,
                            'filename' => $file->original_filename
                        ]);
                    }
                } catch (\Exception $e) {
                    $skippedFiles[] = $file->original_filename;
                    Log::error('Failed to add file to ZIP archive', [
                        'file_id' => $file->id,
                        'filename' => $file->original_filename,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $zip->close();

            if ($addedFiles === 0) {
                unlink($tempZipPath);
                throw new \Exception('No files could be added to the archive. All files may be stored in Google Drive or inaccessible.');
            }

            Log::info('Bulk download ZIP created', [
                'total_requested' => count($fileIds),
                'files_added' => $addedFiles,
                'files_skipped' => count($skippedFiles),
                'skipped_files' => $skippedFiles,
                'zip_file' => $zipFileName
            ]);

            return response()->streamDownload(function () use ($tempZipPath) {
                readfile($tempZipPath);
                unlink($tempZipPath); // Clean up temp file after download
            }, $zipFileName, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"'
            ]);

        } catch (\Exception $e) {
            // Clean up temp file on error
            if (file_exists($tempZipPath)) {
                unlink($tempZipPath);
            }
            throw $e;
        }
    }

    /**
     * Get file content from local storage or Google Drive.
     */
    private function getFileContent(FileUpload $file): ?string
    {
        // Try local file first
        if ($this->hasLocalFile($file)) {
            return Storage::disk('public')->get('uploads/' . $file->filename);
        }

        // For Google Drive files, we can't easily download content in bulk
        // This would require implementing Google Drive API calls which is complex
        // For now, we'll skip Google Drive files in bulk downloads
        return null;
    }

    /**
     * Generate a unique entry name for the ZIP archive.
     */
    private function getUniqueZipEntryName(\ZipArchive $zip, string $originalName, int $fileId): string
    {
        $name = $originalName;
        $counter = 1;
        
        // Check if name already exists in ZIP
        while ($zip->locateName($name) !== false) {
            $pathInfo = pathinfo($originalName);
            $basename = $pathInfo['filename'] ?? $originalName;
            $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
            
            $name = $basename . '_' . $counter . $extension;
            $counter++;
            
            // Fallback to using file ID if we can't generate unique name
            if ($counter > 100) {
                $name = $basename . '_id_' . $fileId . $extension;
                break;
            }
        }
        
        return $name;
    }

    /**
     * Get human-readable ZIP error message.
     */
    private function getZipError(int $code): string
    {
        return match($code) {
            \ZipArchive::ER_MEMORY => 'Memory allocation failure',
            \ZipArchive::ER_NOENT => 'No such file',
            \ZipArchive::ER_OPEN => 'Can\'t open file',
            \ZipArchive::ER_READ => 'Read error',
            \ZipArchive::ER_SEEK => 'Seek error',
            \ZipArchive::ER_WRITE => 'Write error',
            default => 'Unknown error (code: ' . $code . ')'
        };
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