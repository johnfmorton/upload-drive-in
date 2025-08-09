<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\FileUpload;
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

    /**
     * Check if the authenticated user has access to the specified file.
     */
    private function checkFileAccess(FileUpload $file): bool
    {
        $user = Auth::user();
        return $file->company_user_id === $user->id || $file->uploaded_by_user_id === $user->id;
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

        $files = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('employee.file-manager.index', compact('files'));
    }

    /**
     * Display the specified file with detailed information.
     */
    public function show(FileUpload $file): View|JsonResponse
    {
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
    public function update(Request $request, FileUpload $file): RedirectResponse|JsonResponse
    {
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
    public function destroy(FileUpload $file): RedirectResponse|JsonResponse
    {
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
     * Download a file.
     */
    public function download(FileUpload $file)
    {
        if (!$this->checkFileAccess($file)) {
            abort(403, 'Unauthorized access to file');
        }

        try {
            Log::info('File download initiated', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'file_name' => $file->original_filename,
                'file_size' => $file->file_size
            ]);

            // If file is in Google Drive, redirect to Google Drive download
            if ($file->google_drive_file_id) {
                $downloadUrl = "https://drive.google.com/uc?export=download&id={$file->google_drive_file_id}";
                return redirect($downloadUrl);
            }

            // If file is still local, serve it directly
            $path = 'public/uploads/' . $file->filename;
            if (!Storage::exists($path)) {
                abort(404, 'File not found');
            }

            return Storage::download($path, $file->original_filename);
        } catch (\Exception $e) {
            Log::error('File download error', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);

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
     */
    public function preview(FileUpload $file): Response
    {
        if (!$this->checkFileAccess($file)) {
            abort(403, 'Unauthorized access to file');
        }

        try {
            Log::info('File preview accessed', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'ip' => request()->ip()
            ]);

            // If file is in Google Drive, redirect to Google Drive preview
            if ($file->google_drive_file_id) {
                $previewUrl = "https://drive.google.com/file/d/{$file->google_drive_file_id}/preview";
                return redirect($previewUrl);
            }

            // If file is still local, serve it directly
            $path = 'public/uploads/' . $file->filename;
            if (!Storage::exists($path)) {
                abort(404, 'File not found');
            }

            $mimeType = $file->mime_type ?: Storage::mimeType($path);
            return response(Storage::get($path), 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $file->original_filename . '"'
            ]);
        } catch (\Exception $e) {
            return response('Preview not available: ' . $e->getMessage(), 404, [
                'Content-Type' => 'text/plain'
            ]);
        }
    }

    /**
     * Generate a thumbnail for an image file.
     */
    public function thumbnail(FileUpload $file): Response
    {
        if (!$this->checkFileAccess($file)) {
            abort(403, 'Unauthorized access to file');
        }

        try {
            Log::info('File thumbnail accessed', [
                'user_id' => auth()->id(),
                'file_id' => $file->id,
                'ip' => request()->ip()
            ]);

            // For now, return a generic file icon based on mime type
            $iconPath = $this->getFileIcon($file->mime_type);

            return response()->file(public_path($iconPath));
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
        if (str_starts_with($mimeType, 'image/')) {
            return 'images/icons/image-icon.png';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'images/icons/video-icon.png';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'images/icons/audio-icon.png';
        } elseif ($mimeType === 'application/pdf') {
            return 'images/icons/pdf-icon.png';
        } elseif (str_contains($mimeType, 'word') || str_contains($mimeType, 'document')) {
            return 'images/icons/doc-icon.png';
        } elseif (str_contains($mimeType, 'sheet') || str_contains($mimeType, 'excel')) {
            return 'images/icons/excel-icon.png';
        } else {
            return 'images/icons/file-icon.png';
        }
    }
}
