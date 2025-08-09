<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\FileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;

class FileManagerController extends Controller
{
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
     * Show file preview.
     */
    public function preview(FileUpload $file)
    {
        $user = Auth::user();
        
        // Check if user has access to this file
        if ($file->company_user_id !== $user->id && $file->uploaded_by_user_id !== $user->id) {
            abort(403, 'Unauthorized access to file');
        }

        Log::info('Audit: File preview', [
            'user' => $user->email,
            'file' => $file->original_filename,
            'action' => 'preview',
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
    }

    /**
     * Generate file thumbnail.
     */
    public function thumbnail(FileUpload $file)
    {
        $user = Auth::user();
        
        // Check if user has access to this file
        if ($file->company_user_id !== $user->id && $file->uploaded_by_user_id !== $user->id) {
            abort(403, 'Unauthorized access to file');
        }

        Log::info('File thumbnail accessed', [
            'user_id' => $user->id,
            'file_id' => $file->id,
            'ip' => request()->ip()
        ]);

        // For now, return a generic file icon based on mime type
        $iconPath = $this->getFileIcon($file->mime_type);
        
        return response()->file(public_path($iconPath));
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