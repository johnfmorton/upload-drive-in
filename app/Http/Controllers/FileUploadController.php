<?php

namespace App\Http\Controllers;

use App\Jobs\UploadToGoogleDrive;
use App\Models\FileUpload;
use App\Services\FileSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    public function __construct(
        private FileSecurityService $fileSecurityService
    ) {}

    public function index()
    {
        $uploads = FileUpload::where('email', auth()->user()->email)
            ->orderBy('created_at', 'desc')
            ->paginate(config('file-manager.pagination.items_per_page'));

        return view('my-uploads', compact('uploads'));
    }

    public function create()
    {
        return view('client.file-upload');
    }

    /**
     * Store a newly uploaded file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB max per file
            'message' => 'nullable|string|max:1000'
        ]);

        $uploadedFiles = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                // Validate file security (extension, MIME type, content)
                $violations = $this->fileSecurityService->validateFileUpload($file);
                if (!empty($violations)) {
                    return redirect()->back()->withErrors(['files' => $violations[0]['message']]);
                }

                $originalName = $this->fileSecurityService->sanitizeFilename($file->getClientOriginalName());
                $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();

                // Store the file
                $path = Storage::disk('public')->putFileAs('uploads', $file, $filename);

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

        return redirect()->route('client.upload-files')
            ->with('success', 'File uploaded successfully.');
    }
}
