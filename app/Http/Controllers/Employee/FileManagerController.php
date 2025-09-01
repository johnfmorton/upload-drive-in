<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\FileUpload;
use App\Services\FileManagerService;
use App\Services\FilePreviewService;
use App\Services\AuditLogService;
use App\Services\FileSecurityService;
use App\Exceptions\FileAccessException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FileManagerController extends Controller
{
    public function __construct(
        private FileManagerService $fileManagerService,
        private FilePreviewService $filePreviewService,
        private AuditLogService $auditLogService,
        private FileSecurityService $fileSecurityService
    ) {
    }

    /**
     * Check authentication and employee status before actions that require employee access.
     * This method validates that the user is authenticated, has employee role, and can access the specified file.
     */
    private function checkEmployeeAccess(FileUpload $file): void
    {
        if (!auth()->check() || !auth()->user()->isEmployee()) {
            abort(404, 'Please visit the home page to start using the app.');
        }
        
        if (!$file->canBeAccessedBy(auth()->user())) {
            abort(403, 'Access denied to this file');
        }
    }

    /**
     * Check if the authenticated user has access to the specified file.
     * @deprecated Use checkEmployeeAccess() instead for consistency with admin controller
     */
    private function checkFileAccess(FileUpload $file): bool
    {
        $user = Auth::user();
        return $file->company_user_id === $user->id || $file->uploaded_by_user_id === $user->id;
    }

    /**
     * Handle security violation errors with consistent response format.
     */
    private function handleSecurityViolation(string $message)
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'error_type' => 'security_violation'
            ], 403);
        }
        
        return redirect()->back()->with('error', $message);
    }

    /**
     * Handle file access exceptions with consistent error logging and response format.
     */
    private function handleFileAccessException(\App\Exceptions\FileAccessException $e, string $operation)
    {
        \Log::warning("Employee file {$operation} access denied", [
            'user_id' => auth()->id(),
            'error' => $e->getMessage()
        ]);
        
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
                'error_type' => 'access_denied'
            ], 403);
        }

        return redirect()->back()->with('error', $e->getUserMessage());
    }

    /**
     * Handle general exceptions with consistent error logging and response format.
     */
    private function handleGeneralException(\Exception $e, string $operation, FileUpload $file)
    {
        \Log::error("Employee file {$operation} error", [
            'user_id' => auth()->id(),
            'file_id' => $file->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => "Error {$operation}ing file: " . $e->getMessage(),
                'error_type' => "{$operation}_failed",
                'is_retryable' => true
            ], 500);
        }

        return redirect()->back()->with('error', "Error {$operation}ing file. Please try again.");
    }

    /**
     * Display the employee's file manager.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get files uploaded to this employee (where company_user_id matches this employee)
        $query = FileUpload::where('company_user_id', $user->id)
            ->orWhere('uploaded_by_user_id', $user->id); // Also include files uploaded directly by this employee

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        // Apply date filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        // Apply status filter
        if ($request->filled('status')) {
            $status = $request->get('status');
            if ($status === 'uploaded') {
                $query->whereNotNull('google_drive_file_id')->where('google_drive_file_id', '!=', '');
            } elseif ($status === 'processing') {
                $query->where(function ($q) {
                    $q->whereNull('google_drive_file_id')->orWhere('google_drive_file_id', '');
                });
            }
        }

        // Get pagination configuration
        $defaultPerPage = config('file-manager.pagination.items_per_page', 10);
        $maxPerPage = config('file-manager.pagination.max_items_per_page', 100);
        $minPerPage = config('file-manager.pagination.min_items_per_page', 1);
        
        // Get and validate per_page parameter with configuration-based boundaries
        $requestedPerPage = $request->get('per_page', $defaultPerPage);
        
        // Ensure per_page is a positive integer and within configured bounds
        $perPage = max(
            $minPerPage,
            min(
                (int) $requestedPerPage,
                $maxPerPage
            )
        );

        $files = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Add statistics using the service for consistency
        $statistics = $this->fileManagerService->getFileStatistics($user);

        return view('employee.file-manager.index', compact('files', 'statistics'));
    }

    /**
     * Display the specified file with detailed information.
     */
    public function show($username, $fileUpload): View|JsonResponse
    {
        // Handle both route model binding and manual resolution
        if (!$fileUpload instanceof FileUpload) {
            $fileUpload = FileUpload::findOrFail($fileUpload);
        }
        $file = $fileUpload;
        
        if (!$this->checkFileAccess($file)) {
            abort(403, 'Unauthorized access to file');
        }

        try {
            Log::info('File view accessed', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'ip' => request()->ip()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'file' => $file
                ]);
            }

            return view('employee.file-manager.show', compact('file'));
        } catch (\Exception $e) {
            Log::error('Error retrieving file details', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while loading file details.'
                ], 500);
            }

            abort(500, 'An error occurred while loading file details.');
        }
    }

    /**
     * Update the specified file's metadata.
     */
    public function update(Request $request, $username, $fileUpload): RedirectResponse|JsonResponse
    {
        // Handle both route model binding and manual resolution
        if (!$fileUpload instanceof FileUpload) {
            $fileUpload = FileUpload::findOrFail($fileUpload);
        }
        $file = $fileUpload;
        
        if (!$this->checkFileAccess($file)) {
            abort(403, 'Unauthorized access to file');
        }

        try {
            $request->validate([
                'message' => 'nullable|string|max:1000'
            ]);

            Log::info('File update', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'updated_fields' => array_keys($request->only(['message']))
            ]);

            $file->update($request->only(['message']));

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'file' => $file,
                    'message' => 'File updated successfully.'
                ]);
            }

            return redirect()
                ->route('employee.file-manager.show', ['username' => auth()->user()->username, 'file' => $file])
                ->with('success', 'File updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating file', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while updating the file.'
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'An error occurred while updating the file.')
                ->withInput();
        }
    }

    /**
     * Remove the specified file from storage and database.
     */
    public function destroy($username, $fileUpload): RedirectResponse|JsonResponse
    {
        // Handle both route model binding and manual resolution
        if (!$fileUpload instanceof FileUpload) {
            $fileUpload = FileUpload::findOrFail($fileUpload);
        }
        $file = $fileUpload;
        
        if (!$this->checkFileAccess($file)) {
            abort(403, 'Unauthorized access to file');
        }

        try {
            Log::info('File deletion', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'filename' => $file->original_filename
            ]);

            // Delete from local storage if exists
            $localPath = 'public/uploads/' . $file->filename;
            if (Storage::exists($localPath)) {
                Storage::delete($localPath);
            }

            // Delete from database
            $file->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'File deleted successfully.'
                ]);
            }

            return redirect()
                ->route('employee.file-manager.index', ['username' => auth()->user()->username])
                ->with('success', 'File deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting file', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while deleting the file.'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'An error occurred while deleting the file.');
        }
    }

    /**
     * Bulk delete multiple files.
     */
    public function bulkDestroy(Request $request): RedirectResponse|JsonResponse
    {
        try {
            // Handle JSON string from form
            $fileIds = $request->input('file_ids');
            if (is_string($fileIds)) {
                $fileIds = json_decode($fileIds, true);
            }

            $request->merge(['file_ids' => $fileIds]);

            $request->validate([
                'file_ids' => 'required|array',
                'file_ids.*' => 'exists:file_uploads,id'
            ]);

            $user = Auth::user();

            // Check access to all files
            $files = FileUpload::whereIn('id', $request->file_ids)->get();
            $accessibleFiles = $files->filter(function ($file) use ($user) {
                return $file->company_user_id === $user->id || $file->uploaded_by_user_id === $user->id;
            });

            if ($accessibleFiles->isEmpty()) {
                abort(403, 'No accessible files found');
            }

            Log::info('Bulk file deletion', [
                'user_id' => auth()->id(),
                'file_count' => $accessibleFiles->count(),
                'file_ids' => $accessibleFiles->pluck('id')->toArray()
            ]);

            $deletedCount = 0;
            foreach ($accessibleFiles as $file) {
                // Delete from local storage if exists
                $localPath = 'public/uploads/' . $file->filename;
                if (Storage::exists($localPath)) {
                    Storage::delete($localPath);
                }

                $file->delete();
                $deletedCount++;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully deleted {$deletedCount} files.",
                    'deleted_count' => $deletedCount
                ]);
            }

            return redirect()
                ->route('employee.file-manager.index', ['username' => auth()->user()->username])
                ->with('success', "Successfully deleted {$deletedCount} files.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error during bulk file deletion', [
                'user_id' => auth()->id(),
                'file_ids' => $request->file_ids ?? [],
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred during bulk deletion.'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'An error occurred during bulk deletion.');
        }
    }

    /**
     * Download a file with streaming support and progress tracking.
     */
    public function download($username, $fileUpload)
    {
        // Handle both route model binding and manual resolution
        if (!$fileUpload instanceof FileUpload) {
            $fileUpload = FileUpload::findOrFail($fileUpload);
        }
        $file = $fileUpload;
        
        $this->checkEmployeeAccess($file);
        
        try {
            // Security validation
            $securityViolations = $this->fileSecurityService->validateExistingFile($file);
            $highSeverityViolations = array_filter($securityViolations, fn($v) => $v['severity'] === 'high');
            
            if (!empty($highSeverityViolations)) {
                $this->auditLogService->logSecurityViolation('download_blocked_security', auth()->user(), request(), [
                    'file_id' => $file->id,
                    'violations' => $highSeverityViolations
                ]);
                
                return $this->handleSecurityViolation('File download blocked due to security concerns.');
            }

            // Audit log file download
            $this->auditLogService->logFileAccess('download', $file, auth()->user(), request());
            
            // Add download tracking for analytics
            \Log::info('File download initiated', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'file_name' => $file->original_filename,
                'file_size' => $file->file_size
            ]);
            
            // Use FileManagerService for consistent file serving
            return $this->fileManagerService->downloadFile($file, auth()->user());
            
        } catch (\App\Exceptions\FileAccessException $e) {
            return $this->handleFileAccessException($e, 'download');
        } catch (\Exception $e) {
            return $this->handleGeneralException($e, 'download', $file);
        }
    }

    /**
     * Bulk download multiple files as a ZIP archive.
     */
    public function bulkDownload(Request $request)
    {
        // Handle JSON string from form
        $fileIds = $request->input('file_ids');
        if (is_string($fileIds)) {
            $fileIds = json_decode($fileIds, true);
        }

        $request->merge(['file_ids' => $fileIds]);

        $request->validate([
            'file_ids' => 'required|array',
            'file_ids.*' => 'exists:file_uploads,id'
        ]);

        try {
            $user = Auth::user();

            // Check access to all files
            $files = FileUpload::whereIn('id', $request->file_ids)->get();
            $accessibleFiles = $files->filter(function ($file) use ($user) {
                return ($file->company_user_id === $user->id || $file->uploaded_by_user_id === $user->id)
                    && !$file->google_drive_file_id; // Only local files for now
            });

            if ($accessibleFiles->isEmpty()) {
                return redirect()->back()->with('error', 'No accessible local files found for download.');
            }

            Log::info('Bulk download initiated', [
                'user_id' => auth()->id(),
                'file_count' => $accessibleFiles->count(),
                'file_ids' => $accessibleFiles->pluck('id')->toArray()
            ]);

            // Create temporary ZIP file
            $zipFileName = 'files_' . date('Y-m-d_H-i-s') . '.zip';
            $zipPath = storage_path('app/temp/' . $zipFileName);

            // Ensure temp directory exists
            if (!file_exists(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Cannot create ZIP file');
            }

            foreach ($accessibleFiles as $file) {
                $filePath = storage_path('app/public/uploads/' . $file->filename);
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $file->original_filename);
                }
            }

            $zip->close();

            // Return the ZIP file for download and clean up after
            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Bulk download error', [
                'user_id' => auth()->id(),
                'file_ids' => $request->file_ids,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating bulk download: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Error creating bulk download: ' . $e->getMessage());
        }
    }

    /**
     * Preview a file in the browser.
     * Note: This method uses the same implementation as admin controller for consistency.
     */
    public function preview($username, $fileUpload): Response
    {
        // Handle both route model binding and manual resolution
        if (!$fileUpload instanceof FileUpload) {
            $fileUpload = FileUpload::findOrFail($fileUpload);
        }
        $file = $fileUpload;
        
        // Ensure user is authenticated
        if (!auth()->check()) {
            abort(401, 'Authentication required');
        }

        // Check if the authenticated user can access this file
        if (!$file->canBeAccessedBy(auth()->user())) {
            abort(403, 'Access denied to this file');
        }

        try {
            // Security validation for preview
            if (!$this->fileSecurityService->isPreviewSafe($file->mime_type)) {
                $this->auditLogService->logSecurityViolation('preview_blocked_unsafe_type', auth()->user(), request(), [
                    'file_id' => $file->id,
                    'mime_type' => $file->mime_type
                ]);
                
                return response('Preview not available for this file type due to security restrictions.', 403, [
                    'Content-Type' => 'text/plain'
                ]);
            }

            // Generate ETag for conditional requests to prevent cache mix-ups
            $etag = md5($file->id . '_' . $file->file_size . '_' . $file->updated_at->timestamp);
            
            // Check if client has cached version
            if (request()->header('If-None-Match') === '"' . $etag . '"') {
                return response('', 304);
            }

            // Audit log file preview
            $this->auditLogService->logFileAccess('preview', $file, auth()->user(), request());

            return $this->filePreviewService->generatePreview($file, auth()->user());
        } catch (\Exception $e) {
            // Return a simple error response for preview failures
            return response('Preview not available: ' . $e->getMessage(), 404, [
                'Content-Type' => 'text/plain'
            ]);
        }
    }

    /**
     * Generate a thumbnail for an image file.
     */
    public function thumbnail($username, $fileUpload)
    {
        // Handle both route model binding and manual resolution
        if (!$fileUpload instanceof FileUpload) {
            $fileUpload = FileUpload::findOrFail($fileUpload);
        }
        $file = $fileUpload;
        
        if (!$this->checkFileAccess($file)) {
            abort(403, 'Unauthorized access to file');
        }

        try {
            Log::info('File thumbnail accessed', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'ip' => request()->ip()
            ]);

            // If it's an image and exists locally, try to serve it
            if (str_starts_with($file->mime_type, 'image/')) {
                $path = 'public/uploads/' . $file->filename;
                if (Storage::exists($path)) {
                    return response(Storage::get($path), 200, [
                        'Content-Type' => $file->mime_type,
                        'Cache-Control' => 'public, max-age=3600'
                    ]);
                }
            }

            // Return a generic file icon
            $iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14,2 14,8 20,8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10,9 9,9 8,9"></polyline>
            </svg>';

            return response($iconSvg, 200, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'public, max-age=3600'
            ]);
        } catch (\Exception $e) {
            return response('Thumbnail generation failed: ' . $e->getMessage(), 500, [
                'Content-Type' => 'text/plain'
            ]);
        }
    }

    /**
     * Get appropriate file icon based on mime type.
     */
    private function getFileIcon(string $mimeType): string
    {
        // For now, return a generic file icon
        // In the future, you can add specific icons for different file types
        return 'data:image/svg+xml;base64,' . base64_encode('
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14,2 14,8 20,8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10,9 9,9 8,9"></polyline>
            </svg>
        ');
    }

    /**
     * Retry failed uploads for a specific provider.
     */
    public function retryFailedUploads(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'nullable|string|in:google-drive,dropbox,onedrive'
        ]);

        try {
            $user = auth()->user();
            $provider = $validated['provider'] ?? null;
            
            // Build query for failed uploads
            $query = FileUpload::where(function($query) use ($user) {
                    $query->where('company_user_id', $user->id)
                          ->orWhere('uploaded_by_user_id', $user->id);
                })
                ->whereNotNull('cloud_storage_error_type');
            
            // Filter by provider if specified
            if ($provider) {
                $query->where('cloud_storage_provider', $provider);
            }
            
            $failedUploads = $query->get();
            
            if ($failedUploads->isEmpty()) {
                $message = $provider 
                    ? "No failed uploads found for {$provider}."
                    : 'No failed uploads found.';
                
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'retried_count' => 0
                    ]);
                }
                
                return redirect()->back()->with('info', $message);
            }

            $retriedCount = 0;
            
            foreach ($failedUploads as $upload) {
                // Clear error information to allow retry
                $upload->update([
                    'cloud_storage_error_type' => null,
                    'cloud_storage_error_context' => null,
                    'connection_health_at_failure' => null,
                    'retry_recommended_at' => null,
                ]);
                
                // Dispatch upload job
                \App\Jobs\UploadToGoogleDrive::dispatch($upload);
                $retriedCount++;
            }

            Log::info('Retried failed uploads via employee interface', [
                'provider' => $provider,
                'retried_count' => $retriedCount,
                'user_id' => $user->id
            ]);

            $message = $provider 
                ? "Retrying {$retriedCount} failed uploads for {$provider}. Check the queue status for progress."
                : "Retrying {$retriedCount} failed uploads. Check the queue status for progress.";
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'retried_count' => $retriedCount
                ]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to retry failed uploads', [
                'provider' => $validated['provider'] ?? null,
                'error' => $e->getMessage()
            ]);
            
            $errorMessage = 'Failed to retry uploads: ' . $e->getMessage();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Retry a failed file upload.
     */
    public function retry(FileUpload $file): JsonResponse
    {
        try {
            $this->checkEmployeeAccess($file);

            // Check if the file has a cloud storage error
            if (!$file->hasCloudStorageError()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File does not have a cloud storage error to retry'
                ], 400);
            }

            // Check if the error is recoverable
            if (!$file->isCloudStorageErrorRecoverable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This error type cannot be automatically retried. Please check your cloud storage connection.'
                ], 400);
            }

            // Clear the error information
            $file->clearCloudStorageError();

            // Dispatch the upload job again
            \App\Jobs\UploadToGoogleDrive::dispatch($file);

            // Audit log the retry action
            $this->auditLogService->logFileAccess('retry', $file, auth()->user(), request());

            return response()->json([
                'success' => true,
                'message' => 'File upload retry initiated successfully'
            ]);

        } catch (FileAccessException $e) {
            Log::warning('File retry access denied', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage()
            ], 403);

        } catch (\Exception $e) {
            Log::error('File retry error', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrying the file upload'
            ], 500);
        }
    }

    /**
     * Bulk retry multiple failed file uploads.
     */
    public function bulkRetry(Request $request): JsonResponse
    {
        try {
            if (!auth()->check() || !auth()->user()->isEmployee()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $request->validate([
                'file_ids' => 'required|array|min:1',
                'file_ids.*' => 'integer|exists:file_uploads,id'
            ]);

            $fileIds = $request->file_ids;
            $files = FileUpload::whereIn('id', $fileIds)->get();

            // Filter files that the user can access
            $accessibleFiles = $files->filter(function ($file) {
                return $file->canBeAccessedBy(auth()->user());
            });

            if ($accessibleFiles->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No accessible files found to retry'
                ], 400);
            }

            // Filter files that have recoverable errors
            $retryableFiles = $accessibleFiles->filter(function ($file) {
                return $file->hasCloudStorageError() && $file->isCloudStorageErrorRecoverable();
            });

            if ($retryableFiles->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No files with recoverable errors found'
                ], 400);
            }

            $successCount = 0;
            $failureCount = 0;

            foreach ($retryableFiles as $file) {
                try {
                    // Clear the error information
                    $file->clearCloudStorageError();

                    // Dispatch the upload job again
                    \App\Jobs\UploadToGoogleDrive::dispatch($file);

                    $successCount++;

                    // Audit log the retry action
                    $this->auditLogService->logFileAccess('bulk_retry', $file, auth()->user(), request());

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('Bulk retry individual file error', [
                        'user_id' => auth()->id(),
                        'file_id' => $file->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $message = "Retry initiated for {$successCount} file" . ($successCount !== 1 ? 's' : '');
            if ($failureCount > 0) {
                $message .= ", {$failureCount} failed";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'retried_count' => $successCount,
                'failed_count' => $failureCount
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Bulk retry error', [
                'user_id' => auth()->id(),
                'file_ids' => $request->file_ids ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrying the file uploads'
            ], 500);
        }
    }
}
