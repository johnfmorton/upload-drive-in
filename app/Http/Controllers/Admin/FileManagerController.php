<?php

namespace App\Http\Controllers\Admin;

use App\Models\FileUpload;
use App\Services\FileManagerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Enhanced file management controller for admin dashboard.
 * Provides comprehensive CRUD operations for file management.
 */
class FileManagerController extends AdminController
{
    public function __construct(
        private FileManagerService $fileManagerService
    ) {}

    /**
     * Display a listing of files with enhanced filtering and pagination.
     */
    public function index(Request $request): View|JsonResponse
    {
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
     * Download a file.
     */
    public function download(FileUpload $file)
    {
        try {
            return $this->fileManagerService->downloadFile($file);
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
}