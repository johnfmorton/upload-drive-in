<?php

namespace App\Http\Controllers;

use App\Jobs\UploadToGoogleDrive;
use App\Models\FileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    public function create()
    {
        return view('file-upload');
    }

    public function store(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB max per file
            'message' => 'nullable|string|max:1000'
        ]);

        $uploadedFiles = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();

                $path = $file->storeAs('uploads', $filename, 'public');

                $fileUpload = FileUpload::create([
                    'email' => auth()->user()->email,
                    'filename' => $filename,
                    'original_filename' => $originalName,
                    'google_drive_file_id' => '', // Will be updated when uploaded to Google Drive
                    'message' => $request->message,
                    'validation_method' => 'email',
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize()
                ]);

                // Dispatch the Google Drive upload job
                UploadToGoogleDrive::dispatch($fileUpload);

                $uploadedFiles[] = $fileUpload;
            }
        }

        return redirect()->route('upload-files')
            ->with('success', 'Files uploaded successfully. They will be available in your dashboard once uploaded to Google Drive.');
    }
}
