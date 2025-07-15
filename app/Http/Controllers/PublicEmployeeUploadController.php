<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\GoogleDriveManager;

class PublicEmployeeUploadController extends Controller
{
    protected GoogleDriveManager $drive_manager;

    /**
     * Inject the GoogleDriveManager.
     */
    public function __construct(GoogleDriveManager $drive_manager)
    {
        $this->drive_manager = $drive_manager;
    }

    /**
     * Show the public upload page for an employee.
     *
     * @param  Request  $request
     * @param  string   $username
     * @return \Illuminate\View\View
     */
    public function show(Request $request, string $username)
    {
        $user = User::where('username', $username)->firstOrFail();

        $current_folder_id = $user->google_drive_root_folder_id
            ?? config('cloud-storage.providers.google-drive.root_folder_id');

        $current_folder_name = $this->drive_manager->getFolderName(
            $user,
            $current_folder_id
        );

        return view('public-employee.upload-page', compact(
            'username',
            'current_folder_id',
            'current_folder_name'
        ));
    }

    /**
     * Handle public file uploads for an employee.
     *
     * @param  Request  $request
     * @param  string   $username
     * @return \Illuminate\Http\RedirectResponse
     */
    public function upload(Request $request, string $username)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB max per file
            'email' => 'required|email',
            'message' => 'nullable|string|max:1000',
        ]);

        $employee = User::where('username', $username)->firstOrFail();
        
        if (!$employee->isEmployee()) {
            abort(404, 'Employee not found');
        }

        $clientEmail = $request->input('email');
        $message = $request->input('message');

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                $filename = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
                \Illuminate\Support\Facades\Storage::disk('public')->putFileAs('uploads', $file, $filename);

                $upload = \App\Models\FileUpload::create([
                    'email' => $clientEmail,
                    'filename' => $filename,
                    'original_filename' => $originalName,
                    'google_drive_file_id' => '',
                    'message' => $message,
                    'validation_method' => 'employee_public',
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by_user_id' => $employee->id, // Track which employee this upload belongs to
                ]);

                \App\Jobs\UploadToGoogleDrive::dispatch($upload);
            }
        }

        return redirect()->route(
            'public.employee.upload.show',
            ['username' => $username]
        )
        ->with('success', __('messages.files_uploaded_successfully'));
    }

    /**
     * Show the public upload page for an employee by name (extracted from email).
     *
     * @param  string   $name
     * @return \Illuminate\View\View
     */
    public function showByName(string $name)
    {
        // Find employee by extracting name from email
        // We need to escape special characters for the LIKE query
        $escapedName = str_replace(['%', '_'], ['\%', '\_'], $name);
        $employee = User::where('email', 'LIKE', $escapedName . '@%')
            ->where('role', \App\Enums\UserRole::EMPLOYEE)
            ->first();

        if (!$employee) {
            abort(404, 'Employee not found');
        }

        // Check if user is authenticated
        if (!\Illuminate\Support\Facades\Auth::check()) {
            // Store the intended URL in session for redirect after authentication
            session(['intended_url' => request()->url()]);
            
            // Show email validation form for guests
            return view('public-employee.email-validation', compact('name', 'employee'));
        }

        // Check if employee has Google Drive connected
        $hasGoogleDriveConnected = $employee->hasGoogleDriveConnected();

        return view('public-employee.upload-by-name', compact('name', 'employee', 'hasGoogleDriveConnected'));
    }

    /**
     * Handle public file uploads for an employee by name.
     *
     * @param  Request  $request
     * @param  string   $name
     * @return \Illuminate\Http\RedirectResponse
     */
    public function uploadByName(Request $request, string $name)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB max per file
            'email' => 'required|email',
            'message' => 'nullable|string|max:1000',
        ]);

        // Find employee by extracting name from email
        // We need to escape special characters for the LIKE query
        $escapedName = str_replace(['%', '_'], ['\%', '\_'], $name);
        $employee = User::where('email', 'LIKE', $escapedName . '@%')
            ->where('role', \App\Enums\UserRole::EMPLOYEE)
            ->first();

        if (!$employee) {
            abort(404, 'Employee not found');
        }

        $clientEmail = $request->input('email');
        $message = $request->input('message');

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                $filename = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
                \Illuminate\Support\Facades\Storage::disk('public')->putFileAs('uploads', $file, $filename);

                $upload = \App\Models\FileUpload::create([
                    'email' => $clientEmail,
                    'filename' => $filename,
                    'original_filename' => $originalName,
                    'google_drive_file_id' => '',
                    'message' => $message,
                    'validation_method' => 'employee_public',
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by_user_id' => $employee->id, // Track which employee this upload belongs to
                ]);

                \App\Jobs\UploadToGoogleDrive::dispatch($upload);
            }
        }

        return redirect()->route('upload.employee', ['name' => $name])
            ->with('success', __('messages.files_uploaded_successfully'));
    }
}
