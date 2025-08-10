<?php

namespace App\Services;

use App\Exceptions\FileAccessException;
use App\Exceptions\FileManagerException;
use App\Exceptions\GoogleDriveException;
use App\Models\FileUpload;
use App\Models\User;
use App\Services\GoogleDriveService;
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
    public function __construct(
        private GoogleDriveService $googleDriveService,
        private FileMetadataCacheService $cacheService
    ) {
    }
    /**
     * Get filtered and paginated files based on provided criteria.
     * 
     * @param array $filters Filter criteria
     * @param int $perPage Number of items per page
     * @param User|null $user User to filter files for (if null, shows all files)
     * @throws FileManagerException When database query fails
     */
    public function getFilteredFiles(array $filters, int $perPage = 15, ?User $user = null): LengthAwarePaginator
    {
        try {
        $query = FileUpload::query()
            ->with(['clientUser', 'companyUser', 'uploadedByUser']);

        // Apply user-specific filtering if user is provided
        if ($user) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('company_user_id', $user->id)
                  ->orWhere('uploaded_by_user_id', $user->id);
            });
        }

        // Apply search filter with enhanced multi-column search
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $searchTerms = explode(' ', $search);
            
            $query->where(function (Builder $q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    if (strlen($term) >= 2) { // Only search terms with 2+ characters
                        $q->where(function (Builder $subQ) use ($term) {
                            $subQ->where('original_filename', 'like', "%{$term}%")
                                 ->orWhere('email', 'like', "%{$term}%")
                                 ->orWhere('message', 'like', "%{$term}%")
                                 ->orWhere('mime_type', 'like', "%{$term}%");
                        });
                    }
                }
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

        // Apply date range filter with enhanced date handling
        if (!empty($filters['date_from'])) {
            try {
                $dateFrom = \Carbon\Carbon::parse($filters['date_from'])->startOfDay();
                $query->where('created_at', '>=', $dateFrom);
            } catch (\Exception $e) {
                Log::warning('Invalid date_from filter', ['date' => $filters['date_from']]);
            }
        }
        
        if (!empty($filters['date_to'])) {
            try {
                $dateTo = \Carbon\Carbon::parse($filters['date_to'])->endOfDay();
                $query->where('created_at', '<=', $dateTo);
            } catch (\Exception $e) {
                Log::warning('Invalid date_to filter', ['date' => $filters['date_to']]);
            }
        }

        // Apply user email filter with exact and partial matching
        if (!empty($filters['user_email'])) {
            $email = trim($filters['user_email']);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // Exact email match
                $query->where('email', $email);
            } else {
                // Partial email match
                $query->where('email', 'like', "%{$email}%");
            }
        }

        // Apply enhanced file type filter
        if (!empty($filters['file_type'])) {
            $fileType = $filters['file_type'];
            
            // Handle both specific MIME types and general categories
            if (str_contains($fileType, '/')) {
                // Specific MIME type (e.g., "image/jpeg")
                $query->where('mime_type', $fileType);
            } else {
                // General category (e.g., "image", "document")
                $query->where(function (Builder $q) use ($fileType) {
                    switch ($fileType) {
                        case 'image':
                            $q->where('mime_type', 'like', 'image/%');
                            break;
                        case 'document':
                            $q->where(function (Builder $subQ) {
                                $subQ->where('mime_type', 'like', 'application/pdf')
                                     ->orWhere('mime_type', 'like', 'application/msword')
                                     ->orWhere('mime_type', 'like', 'application/vnd.openxmlformats%')
                                     ->orWhere('mime_type', 'like', 'text/%');
                            });
                            break;
                        case 'video':
                            $q->where('mime_type', 'like', 'video/%');
                            break;
                        case 'audio':
                            $q->where('mime_type', 'like', 'audio/%');
                            break;
                        case 'archive':
                            $q->where(function (Builder $subQ) {
                                $subQ->where('mime_type', 'like', 'application/zip')
                                     ->orWhere('mime_type', 'like', 'application/x-rar%')
                                     ->orWhere('mime_type', 'like', 'application/x-7z%');
                            });
                            break;
                        default:
                            $q->where('mime_type', 'like', "{$fileType}%");
                    }
                });
            }
        }

        // Apply file size filters
        if (!empty($filters['file_size_min'])) {
            $minSize = $this->parseFileSize($filters['file_size_min']);
            if ($minSize > 0) {
                $query->where('file_size', '>=', $minSize);
            }
        }
        
        if (!empty($filters['file_size_max'])) {
            $maxSize = $this->parseFileSize($filters['file_size_max']);
            if ($maxSize > 0) {
                $query->where('file_size', '<=', $maxSize);
            }
        }

        // Apply sorting with enhanced options
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        $allowedSortFields = [
            'created_at', 'updated_at', 'original_filename', 'file_size', 
            'email', 'mime_type'
        ];
        
        if (in_array($sortBy, $allowedSortFields)) {
            if ($sortBy === 'original_filename') {
                // Case-insensitive sorting for filenames
                $query->orderByRaw("LOWER(original_filename) {$sortDirection}");
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

            return $query->paginate($perPage);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database query error in getFilteredFiles', [
                'filters' => $filters,
                'per_page' => $perPage,
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? 'N/A'
            ]);
            
            throw new FileManagerException(
                message: "Database query failed: {$e->getMessage()}",
                userMessage: "Unable to load files due to a database error. Please try again or contact support.",
                code: 500,
                previous: $e,
                context: [
                    'filters' => $filters,
                    'type' => 'database_query_error'
                ],
                isRetryable: true
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error in getFilteredFiles', [
                'filters' => $filters,
                'per_page' => $perPage,
                'error' => $e->getMessage()
            ]);
            
            throw new FileManagerException(
                message: "Unexpected error while filtering files: {$e->getMessage()}",
                userMessage: "An unexpected error occurred while loading files. Please try again.",
                code: 500,
                previous: $e,
                context: [
                    'filters' => $filters,
                    'type' => 'unexpected_filter_error'
                ],
                isRetryable: true
            );
        }
    }

    /**
     * Get comprehensive file statistics for dashboard.
     * 
     * @param User|null $user User to get statistics for (if null, gets global statistics)
     */
    public function getFileStatistics(?User $user = null): array
    {
        if ($user) {
            // Calculate user-specific statistics
            $query = FileUpload::where(function (Builder $q) use ($user) {
                $q->where('company_user_id', $user->id)
                  ->orWhere('uploaded_by_user_id', $user->id);
            });

            $total = $query->count();
            $pending = $query->where(function (Builder $q) {
                $q->whereNull('google_drive_file_id')
                  ->orWhere('google_drive_file_id', '');
            })->count();
            $completed = $total - $pending;
            $totalSize = $query->sum('file_size') ?: 0;

            return [
                'total' => $total,
                'pending' => $pending,
                'completed' => $completed,
                'total_size' => $totalSize,
                'total_size_human' => $this->formatBytes($totalSize),
                'average_size' => $total > 0 ? round($totalSize / $total) : 0,
                'upload_rate_today' => $query->whereDate('created_at', today())->count(),
                'upload_rate_week' => $query->where('created_at', '>=', now()->subWeek())->count(),
                'upload_rate_month' => $query->where('created_at', '>=', now()->subMonth())->count(),
            ];
        }

        return $this->cacheService->getFileStatistics();
    }



    /**
     * Get detailed information about a specific file.
     */
    public function getFileDetails(FileUpload $file): array
    {
        $cachedMetadata = $this->cacheService->getFileMetadata($file);
        
        return [
            'file' => $file->load(['clientUser', 'companyUser', 'uploadedByUser']),
            'size_formatted' => $cachedMetadata['file_size_human'],
            'upload_date_formatted' => $file->created_at->format('M j, Y g:i A'),
            'is_pending' => $cachedMetadata['is_pending'],
            'has_local_copy' => $this->hasLocalFile($file),
            'google_drive_url' => $cachedMetadata['google_drive_url'],
            'file_extension' => $cachedMetadata['file_extension'],
            'mime_type_category' => $cachedMetadata['mime_type_category']
        ];
    }

    /**
     * Update file metadata.
     */
    public function updateFileMetadata(FileUpload $file, array $data): FileUpload
    {
        $file->update(array_filter($data));
        
        // Invalidate cache after update
        $this->cacheService->invalidateFileCache($file);
        
        Log::info('File metadata updated', [
            'file_id' => $file->id,
            'updated_fields' => array_keys(array_filter($data))
        ]);

        return $file->fresh();
    }

    /**
     * Delete a file from all storage locations and database with comprehensive error handling.
     */
    public function deleteFile(FileUpload $file): bool
    {
        try {
            $errors = [];
            $warnings = [];

            // Delete from Google Drive if exists
            if ($file->google_drive_file_id) {
                try {
                    $deleted = $file->deleteFromGoogleDrive();
                    if (!$deleted) {
                        $warnings[] = 'File may still exist in Google Drive';
                        Log::warning('Failed to delete file from Google Drive', [
                            'file_id' => $file->id,
                            'google_drive_file_id' => $file->google_drive_file_id
                        ]);
                    }
                } catch (\Exception $e) {
                    $warnings[] = 'Google Drive deletion failed: ' . $e->getMessage();
                    Log::error('Google Drive deletion error', [
                        'file_id' => $file->id,
                        'google_drive_file_id' => $file->google_drive_file_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Delete local file if exists
            if ($this->hasLocalFile($file)) {
                try {
                    $deleted = Storage::disk('public')->delete('uploads/' . $file->filename);
                    if (!$deleted) {
                        $warnings[] = 'Local file may still exist';
                    }
                } catch (\Exception $e) {
                    $warnings[] = 'Local file deletion failed: ' . $e->getMessage();
                    Log::error('Local file deletion error', [
                        'file_id' => $file->id,
                        'filename' => $file->filename,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Delete database record
            try {
                // Invalidate cache before deletion
                $this->cacheService->invalidateFileCache($file);
                
                $file->delete();
            } catch (\Exception $e) {
                throw new FileManagerException(
                    message: "Failed to delete file record from database: {$e->getMessage()}",
                    userMessage: "The file could not be completely removed. Please try again or contact support.",
                    code: 500,
                    previous: $e,
                    context: [
                        'file_id' => $file->id,
                        'warnings' => $warnings,
                        'type' => 'database_deletion_failed'
                    ]
                );
            }

            if (!empty($warnings)) {
                Log::warning('File deleted with warnings', [
                    'file_id' => $file->id,
                    'warnings' => $warnings
                ]);
            } else {
                Log::info('File deleted successfully', ['file_id' => $file->id]);
            }
            
            return true;
        } catch (FileManagerException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new FileManagerException(
                message: "Unexpected error during file deletion: {$e->getMessage()}",
                userMessage: "An unexpected error occurred while deleting the file. Please try again.",
                code: 500,
                previous: $e,
                context: [
                    'file_id' => $file->id,
                    'type' => 'unexpected_deletion_error'
                ]
            );
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
     * Download a file from local storage or Google Drive with streaming support.
     */
    public function downloadFile(FileUpload $file, ?User $user = null): StreamedResponse
    {
        Log::info('Starting file download', [
            'file_id' => $file->id,
            'filename' => $file->original_filename,
            'has_local' => $this->hasLocalFile($file),
            'has_google_drive' => !empty($file->google_drive_file_id)
        ]);

        // Try local file first
        if ($this->hasLocalFile($file)) {
            return $this->downloadLocalFile($file);
        }

        // Try Google Drive if file has Google Drive ID
        if ($file->google_drive_file_id) {
            return $this->downloadGoogleDriveFile($file, $user);
        }

        throw new \Exception('File not found in local storage or Google Drive.');
    }

    /**
     * Download file from local storage with proper headers.
     */
    private function downloadLocalFile(FileUpload $file): StreamedResponse
    {
        $filePath = 'uploads/' . $file->filename;
        
        Log::info('Downloading file from local storage', [
            'file_id' => $file->id,
            'path' => $filePath
        ]);

        return Storage::disk('public')->download($filePath, $file->original_filename, [
            'Content-Type' => $file->mime_type,
            'Content-Length' => $file->file_size,
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    /**
     * Download file from Google Drive with streaming support.
     */
    private function downloadGoogleDriveFile(FileUpload $file, ?User $user = null): StreamedResponse
    {
        // Find a user with Google Drive access
        $driveUser = $this->findGoogleDriveUser($user);
        
        if (!$driveUser) {
            throw new \Exception('No Google Drive connection available for download. Please ensure an admin has connected their Google Drive account.');
        }

        Log::info('Downloading file from Google Drive', [
            'file_id' => $file->id,
            'google_drive_file_id' => $file->google_drive_file_id,
            'drive_user_id' => $driveUser->id
        ]);

        try {
            // For large files, use streaming download
            if ($file->file_size > 10 * 1024 * 1024) { // 10MB threshold
                return $this->streamGoogleDriveFile($file, $driveUser);
            } else {
                return $this->downloadGoogleDriveFileContent($file, $driveUser);
            }
        } catch (\Exception $e) {
            Log::error('Failed to download file from Google Drive', [
                'file_id' => $file->id,
                'google_drive_file_id' => $file->google_drive_file_id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to download file from Google Drive: ' . $e->getMessage());
        }
    }

    /**
     * Stream large files from Google Drive.
     */
    private function streamGoogleDriveFile(FileUpload $file, User $driveUser): StreamedResponse
    {
        return response()->streamDownload(function () use ($file, $driveUser) {
            $stream = $this->googleDriveService->downloadFileStream($driveUser, $file->google_drive_file_id);
            
            // Stream the file content in chunks
            while (!feof($stream)) {
                echo fread($stream, 8192); // 8KB chunks
                flush();
            }
            
            fclose($stream);
        }, $file->original_filename, [
            'Content-Type' => $file->mime_type,
            'Content-Length' => $file->file_size,
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Accept-Ranges' => 'bytes'
        ]);
    }

    /**
     * Download smaller files from Google Drive as content.
     */
    private function downloadGoogleDriveFileContent(FileUpload $file, User $driveUser): StreamedResponse
    {
        $content = $this->googleDriveService->downloadFile($driveUser, $file->google_drive_file_id);
        
        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $file->original_filename, [
            'Content-Type' => $file->mime_type,
            'Content-Length' => strlen($content),
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    /**
     * Find a user with Google Drive access for downloading files.
     */
    private function findGoogleDriveUser(?User $requestingUser = null): ?User
    {
        // If requesting user has Google Drive access, use their account
        if ($requestingUser && $requestingUser->hasGoogleDriveConnected()) {
            return $requestingUser;
        }

        // Otherwise, find an admin with Google Drive access
        return User::where('role', \App\Enums\UserRole::ADMIN)
            ->whereHas('googleDriveToken')
            ->first();
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

    /**
     * Get available filter options for the UI.
     */
    public function getFilterOptions(): array
    {
        return $this->cacheService->getFilterOptions();
    }

    /**
     * Get human-readable label for MIME type.
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
            'text/csv' => 'CSV File',
            'video/mp4' => 'MP4 Video',
            'video/avi' => 'AVI Video',
            'video/quicktime' => 'QuickTime Video',
            'audio/mpeg' => 'MP3 Audio',
            'audio/wav' => 'WAV Audio',
            'application/zip' => 'ZIP Archive',
            'application/x-rar-compressed' => 'RAR Archive',
        ];

        return $labels[$mimeType] ?? ucfirst(str_replace(['/', '-', '_'], [' ', ' ', ' '], $mimeType));
    }

    /**
     * Parse file size string to bytes.
     * Supports formats like "10MB", "1.5GB", "500KB", etc.
     */
    private function parseFileSize(string $sizeString): int
    {
        $sizeString = trim(strtoupper($sizeString));
        
        // Extract number and unit
        if (preg_match('/^(\d+(?:\.\d+)?)\s*([KMGT]?B?)$/', $sizeString, $matches)) {
            $number = (float) $matches[1];
            $unit = $matches[2] ?? 'B';
            
            $multipliers = [
                'B' => 1,
                'KB' => 1024,
                'MB' => 1024 * 1024,
                'GB' => 1024 * 1024 * 1024,
                'TB' => 1024 * 1024 * 1024 * 1024,
                'K' => 1024,
                'M' => 1024 * 1024,
                'G' => 1024 * 1024 * 1024,
                'T' => 1024 * 1024 * 1024 * 1024,
            ];
            
            return (int) ($number * ($multipliers[$unit] ?? 1));
        }
        
        // If it's just a number, treat as bytes
        if (is_numeric($sizeString)) {
            return (int) $sizeString;
        }
        
        return 0;
    }
}