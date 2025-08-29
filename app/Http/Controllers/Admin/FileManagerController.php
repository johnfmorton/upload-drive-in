<?php

namespace App\Http\Controllers\Admin;

use App\Models\FileUpload;
use App\Services\FileManagerService;
use App\Services\FilePreviewService;
use App\Services\AuditLogService;
use App\Services\FileSecurityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Enhanced file management controller for admin dashboard.
 * Provides comprehensive CRUD operations for file management.
 */
class FileManagerController extends AdminController
{
    public function __construct(
        private FileManagerService $fileManagerService,
        private FilePreviewService $filePreviewService,
        private AuditLogService $auditLogService,
        private FileSecurityService $fileSecurityService
    ) {
        // Override parent constructor to handle authorization per method
        // instead of blanket admin-only restriction
    }

    /**
     * Check authentication and admin status before actions that require admin access.
     * Preview and thumbnail methods are excluded from this check.
     */
    protected function checkAdminAccess()
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(404, 'Please visit the home page to start using the app.');
        }
    }

    /**
     * Check if the current user has access to the specified file.
     */
    protected function checkFileAccess(FileUpload $file)
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(404, 'Please visit the home page to start using the app.');
        }

        // Check if user has access to this file
        if (!$file->canBeAccessedBy($user)) {
            abort(404, 'File not found.');
        }
    }

    /**
     * Display a listing of files with enhanced filtering and pagination.
     */
    public function index(Request $request): View|JsonResponse
    {
        try {
            $this->checkAdminAccess();
            
            $filters = $request->only([
                'search',
                'status',
                'date_from',
                'date_to',
                'user_email',
                'file_type',
                'file_size_min',
                'file_size_max',
                'sort_by',
                'sort_direction'
            ]);

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
            
            // Admin users get files related to them (uploaded for them or by them)
            $files = $this->fileManagerService->getFilteredFiles($filters, $perPage, auth()->user());
            $statistics = $this->fileManagerService->getFileStatistics(auth()->user());
            $filterOptions = $this->fileManagerService->getFilterOptions();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'files' => $files,
                    'statistics' => $statistics,
                    'filter_options' => $filterOptions
                ]);
            }

            return view('admin.file-manager.index', compact('files', 'statistics', 'filters', 'filterOptions'));
            
        } catch (\App\Exceptions\FileManagerException $e) {
            \Log::error('File manager index error', [
                'user_id' => auth()->id(),
                'filters' => $filters ?? [],
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            if ($request->expectsJson()) {
                return $e->render($request);
            }

            return redirect()->route('admin.dashboard')
                ->with('error', $e->getUserMessage());
                
        } catch (\Exception $e) {
            \Log::error('Unexpected error in file manager index', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An unexpected error occurred while loading files. Please try again.'
                ], 500);
            }

            return redirect()->route('admin.dashboard')
                ->with('error', 'An unexpected error occurred while loading files. Please try again.');
        }
    }

    /**
     * Display the specified file with detailed information.
     */
    public function show(FileUpload $file): View|JsonResponse
    {
        try {
            $this->checkAdminAccess();
            $this->checkFileAccess($file);
            
            // Audit log file view
            $this->auditLogService->logFileAccess('view', $file, auth()->user(), request());
            
            $fileDetails = $this->fileManagerService->getFileDetails($file);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'file' => $fileDetails
                ]);
            }

            return view('admin.file-manager.show', compact('file', 'fileDetails'));
            
        } catch (\App\Exceptions\FileAccessException $e) {
            \Log::warning('File access denied', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getUserMessage()
                ], 403);
            }

            abort(403, $e->getUserMessage());
                
        } catch (\App\Exceptions\FileManagerException $e) {
            \Log::error('File details retrieval error', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getUserMessage()
                ], 500);
            }

            abort(500, $e->getUserMessage());
                
        } catch (\Exception $e) {
            \Log::error('Unexpected error retrieving file details', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An unexpected error occurred while loading file details. Please try again.'
                ], 500);
            }

            abort(500, 'An unexpected error occurred while loading file details. Please try again.');
        }
    }

    /**
     * Update the specified file's metadata.
     */
    public function update(Request $request, FileUpload $file): RedirectResponse|JsonResponse
    {
        try {
            $this->checkAdminAccess();
            $this->checkFileAccess($file);
            
            $request->validate([
                'message' => 'nullable|string|max:1000',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50'
            ]);

            // Audit log file update
            $this->auditLogService->logFileAccess('update', $file, auth()->user(), $request, [
                'updated_fields' => array_keys($request->only(['message', 'tags']))
            ]);

            $updatedFile = $this->fileManagerService->updateFileMetadata(
                $file,
                $request->only(['message', 'tags'])
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'file' => $updatedFile,
                    'message' => 'File updated successfully.'
                ]);
            }

            return redirect()
                ->route('admin.file-manager.show', $file)
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
                
        } catch (\App\Exceptions\FileManagerException $e) {
            \Log::error('File update error', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            if ($request->expectsJson()) {
                return $e->render($request);
            }

            return redirect()
                ->back()
                ->with('error', $e->getUserMessage())
                ->withInput();
                
        } catch (\Exception $e) {
            \Log::error('Unexpected error updating file', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An unexpected error occurred while updating the file. Please try again.'
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'An unexpected error occurred while updating the file. Please try again.')
                ->withInput();
        }
    }

    /**
     * Remove the specified file from storage and database.
     */
    public function destroy(FileUpload $file): RedirectResponse|JsonResponse
    {
        try {
            $this->checkAdminAccess();
            $this->checkFileAccess($file);
            
            // Audit log file deletion
            $this->auditLogService->logFileAccess('delete', $file, auth()->user(), request());
            
            $this->fileManagerService->deleteFile($file);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'File deleted successfully.'
                ]);
            }

            return redirect()
                ->route('admin.file-manager.index')
                ->with('success', 'File deleted successfully.');

        } catch (\App\Exceptions\FileAccessException $e) {
            \Log::warning('File deletion access denied', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            if (request()->expectsJson()) {
                return $e->render(request());
            }

            return redirect()->route('admin.file-manager.index')
                ->with('error', $e->getUserMessage());
                
        } catch (\App\Exceptions\FileManagerException $e) {
            \Log::error('File deletion error', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            if (request()->expectsJson()) {
                return $e->render(request());
            }

            return redirect()->back()
                ->with('error', $e->getUserMessage());
                
        } catch (\Exception $e) {
            \Log::error('Unexpected error deleting file', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An unexpected error occurred while deleting the file. Please try again.'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'An unexpected error occurred while deleting the file. Please try again.');
        }
    }

    /**
     * Bulk delete multiple files.
     */
    public function bulkDestroy(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $this->checkAdminAccess();
            
            $request->validate([
                'file_ids' => 'required|array',
                'file_ids.*' => 'exists:file_uploads,id'
            ]);

            // Audit log bulk deletion
            $this->auditLogService->logBulkFileOperation('delete', $request->file_ids, auth()->user(), $request);

            $result = $this->fileManagerService->bulkDeleteFiles($request->file_ids);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully deleted {$result} files.",
                    'deleted_count' => $result
                ]);
            }

            return redirect()
                ->route('admin.file-manager.index')
                ->with('success', "Successfully deleted {$result} files.");

        } catch (\App\Exceptions\FileAccessException $e) {
            \Log::warning('Bulk file deletion access denied', [
                'user_id' => auth()->id(),
                'file_ids' => $request->file_ids ?? [],
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            if ($request->expectsJson()) {
                return $e->render($request);
            }

            return redirect()->route('admin.file-manager.index')
                ->with('error', $e->getUserMessage());
                
        } catch (\App\Exceptions\FileManagerException $e) {
            \Log::error('Bulk file deletion error', [
                'user_id' => auth()->id(),
                'file_ids' => $request->file_ids ?? [],
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            if ($request->expectsJson()) {
                return $e->render($request);
            }

            return redirect()->back()
                ->with('error', $e->getUserMessage());
                
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
            \Log::error('Unexpected error during bulk file deletion', [
                'user_id' => auth()->id(),
                'file_ids' => $request->file_ids ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An unexpected error occurred during bulk deletion. Please try again.'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'An unexpected error occurred during bulk deletion. Please try again.');
        }
    }

    /**
     * Process pending uploads.
     */
    public function processPending(): RedirectResponse|JsonResponse
    {
        $this->checkAdminAccess();
        
        try {
            $result = $this->fileManagerService->processPendingUploads();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'processed_count' => $result['count']
                ]);
            }

            return redirect()
                ->route('admin.file-manager.index')
                ->with('success', $result['message']);

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error processing pending uploads: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Error processing pending uploads: ' . $e->getMessage());
        }
    }

    /**
     * Download a file with streaming support and progress tracking.
     */
    public function download(FileUpload $file)
    {
        $this->checkAdminAccess();
        $this->checkFileAccess($file);
        
        try {
            // Security validation
            $securityViolations = $this->fileSecurityService->validateExistingFile($file);
            $highSeverityViolations = array_filter($securityViolations, fn($v) => $v['severity'] === 'high');
            
            if (!empty($highSeverityViolations)) {
                $this->auditLogService->logSecurityViolation('download_blocked_security', auth()->user(), request(), [
                    'file_id' => $file->id,
                    'violations' => $highSeverityViolations
                ]);
                
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File download blocked due to security concerns.',
                        'error_type' => 'security_violation'
                    ], 403);
                }
                
                return redirect()->back()->with('error', 'File download blocked due to security concerns.');
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
            
            // Get file size for Content-Length header to enable progress tracking
            $response = $this->fileManagerService->downloadFile($file, auth()->user());
            
            // Return streaming response with proper headers for download progress tracking
            return $response;
        } catch (\App\Exceptions\FileAccessException $e) {
            \Log::warning('File download access denied', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getUserMessage(),
                    'error_type' => 'access_denied'
                ], 403);
            }

            return redirect()
                ->back()
                ->with('error', $e->getUserMessage());
        } catch (\Exception $e) {
            \Log::error('File download error', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error downloading file: ' . $e->getMessage(),
                    'error_type' => 'download_failed',
                    'is_retryable' => true
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Error downloading file: ' . $e->getMessage());
        }
    }

    /**
     * Bulk download multiple files as a ZIP archive.
     */
    public function bulkDownload(Request $request)
    {
        $this->checkAdminAccess();
        
        $request->validate([
            'file_ids' => 'required|array',
            'file_ids.*' => 'exists:file_uploads,id'
        ]);

        try {
            $user = auth()->user();
            
            // Security validation for all files - only get files the user has access to
            $files = FileUpload::whereIn('id', $request->file_ids)
                ->where(function($query) use ($user) {
                    $query->where('company_user_id', $user->id)
                          ->orWhere('uploaded_by_user_id', $user->id);
                })
                ->get();
            $blockedFiles = [];
            
            foreach ($files as $file) {
                $securityViolations = $this->fileSecurityService->validateExistingFile($file);
                $highSeverityViolations = array_filter($securityViolations, fn($v) => $v['severity'] === 'high');
                
                if (!empty($highSeverityViolations)) {
                    $blockedFiles[] = $file->id;
                }
            }
            
            if (!empty($blockedFiles)) {
                $this->auditLogService->logSecurityViolation('bulk_download_blocked_security', auth()->user(), $request, [
                    'blocked_files' => $blockedFiles,
                    'total_files' => count($request->file_ids)
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Some files blocked due to security concerns.',
                        'error_type' => 'security_violation',
                        'blocked_files' => $blockedFiles
                    ], 403);
                }
                
                return redirect()->back()->with('error', 'Some files blocked due to security concerns.');
            }

            // Audit log bulk download
            $this->auditLogService->logBulkFileOperation('download', $request->file_ids, auth()->user(), $request);

            // Log bulk download attempt for analytics
            \Log::info('Bulk download initiated', [
                'user_id' => auth()->id(),
                'file_count' => count($request->file_ids),
                'file_ids' => $request->file_ids
            ]);
            
            // Get file information for progress tracking - only for files the user has access to
            $totalSize = FileUpload::whereIn('id', $request->file_ids)
                ->where(function($query) use ($user) {
                    $query->where('company_user_id', $user->id)
                          ->orWhere('uploaded_by_user_id', $user->id);
                })
                ->sum('file_size');
            
            // Add headers for download progress tracking
            $response = $this->fileManagerService->bulkDownloadFiles($request->file_ids);
            
            // Add additional headers for better download experience
            if (method_exists($response, 'header')) {
                $response->header('X-File-Count', count($request->file_ids));
                $response->header('X-Total-Size', $totalSize);
            }
            
            return $response;
        } catch (\App\Exceptions\FileAccessException $e) {
            \Log::warning('Bulk download access denied', [
                'user_id' => auth()->id(),
                'file_ids' => $request->file_ids,
                'error' => $e->getMessage()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getUserMessage(),
                    'error_type' => 'access_denied'
                ], 403);
            }

            return redirect()
                ->back()
                ->with('error', $e->getUserMessage());
        } catch (\Exception $e) {
            \Log::error('Bulk download error', [
                'user_id' => auth()->id(),
                'file_ids' => $request->file_ids,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating bulk download: ' . $e->getMessage(),
                    'error_type' => 'download_failed',
                    'is_retryable' => true
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Error creating bulk download: ' . $e->getMessage());
        }
    }

    /**
     * Preview a file in the browser.
     * Note: This method bypasses admin-only restriction for file access control.
     */
    public function preview(FileUpload $file): Response
    {
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
     * Note: This method bypasses admin-only restriction for file access control.
     */
    public function thumbnail(FileUpload $file): Response
    {
        // Override admin-only restriction for thumbnail functionality
        // The FilePreviewService will handle proper access control based on user roles
        if (!auth()->check()) {
            abort(401, 'Authentication required');
        }

        // Check if the authenticated user can access this file
        if (!$file->canBeAccessedBy(auth()->user())) {
            abort(403, 'Access denied to this file');
        }

        try {
            // Security validation for thumbnail
            if (!str_starts_with($file->mime_type, 'image/')) {
                $this->auditLogService->logSecurityViolation('thumbnail_blocked_non_image', auth()->user(), request(), [
                    'file_id' => $file->id,
                    'mime_type' => $file->mime_type
                ]);
                
                return response('Thumbnail not available for non-image files.', 403, [
                    'Content-Type' => 'text/plain'
                ]);
            }

            // Generate ETag for conditional requests to prevent cache mix-ups
            $etag = md5($file->id . '_' . $file->file_size . '_150x150_' . $file->updated_at->timestamp);
            
            // Check if client has cached version
            if (request()->header('If-None-Match') === '"' . $etag . '"') {
                return response('', 304);
            }

            // Audit log thumbnail access (less verbose than full file access)
            \Log::info('File thumbnail accessed', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'ip' => request()->ip()
            ]);

            $thumbnail = $this->filePreviewService->getThumbnail($file, auth()->user());
            
            if ($thumbnail === null) {
                // Return a 404 response if thumbnail cannot be generated
                return response('Thumbnail not available', 404, [
                    'Content-Type' => 'text/plain'
                ]);
            }
            
            return $thumbnail;
        } catch (\Exception $e) {
            return response('Thumbnail generation failed: ' . $e->getMessage(), 500, [
                'Content-Type' => 'text/plain'
            ]);
        }
    }
}