<?php

namespace App\Http\Controllers\Admin;

use App\Models\FileUpload;
use App\Services\FileManagerService;
use App\Services\FilePreviewService;
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
        private FilePreviewService $filePreviewService
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
     * Display a listing of files with enhanced filtering and pagination.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->checkAdminAccess();
        $filters = $request->only([
            'search',
            'status',
            'date_from',
            'date_to',
            'user_email',
            'file_type',
            'sort_by',
            'sort_direction'
        ]);

        $perPage = $request->get('per_page', 15);
        
        $files = $this->fileManagerService->getFilteredFiles($filters, $perPage);
        $statistics = $this->fileManagerService->getFileStatistics();

        if ($request->expectsJson()) {
            return response()->json([
                'files' => $files,
                'statistics' => $statistics
            ]);
        }

        return view('admin.file-manager.index', compact('files', 'statistics', 'filters'));
    }

    /**
     * Display the specified file with detailed information.
     */
    public function show(FileUpload $file): View|JsonResponse
    {
        $this->checkAdminAccess();
        
        $fileDetails = $this->fileManagerService->getFileDetails($file);

        if (request()->expectsJson()) {
            return response()->json($fileDetails);
        }

        return view('admin.file-manager.show', compact('file', 'fileDetails'));
    }

    /**
     * Update the specified file's metadata.
     */
    public function update(Request $request, FileUpload $file): RedirectResponse|JsonResponse
    {
        $this->checkAdminAccess();
        
        $request->validate([
            'message' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50'
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
    }

    /**
     * Remove the specified file from storage and database.
     */
    public function destroy(FileUpload $file): RedirectResponse|JsonResponse
    {
        $this->checkAdminAccess();
        
        try {
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

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting file: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Error deleting file: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete multiple files.
     */
    public function bulkDestroy(Request $request): RedirectResponse|JsonResponse
    {
        $this->checkAdminAccess();
        
        $request->validate([
            'file_ids' => 'required|array',
            'file_ids.*' => 'exists:file_uploads,id'
        ]);

        try {
            $deletedCount = $this->fileManagerService->bulkDeleteFiles($request->file_ids);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully deleted {$deletedCount} files."
                ]);
            }

            return redirect()
                ->route('admin.file-manager.index')
                ->with('success', "Successfully deleted {$deletedCount} files.");

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error during bulk deletion: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Error during bulk deletion: ' . $e->getMessage());
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
        
        try {
            return $this->fileManagerService->downloadFile($file, auth()->user());
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error downloading file: ' . $e->getMessage()
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
            return $this->fileManagerService->bulkDownloadFiles($request->file_ids);
        } catch (\Exception $e) {
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
     * Note: This method bypasses admin-only restriction for file access control.
     */
    public function preview(FileUpload $file): Response
    {
        // Override admin-only restriction for preview functionality
        // The FilePreviewService will handle proper access control based on user roles
        if (!auth()->check()) {
            abort(401, 'Authentication required');
        }

        try {
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

        try {
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