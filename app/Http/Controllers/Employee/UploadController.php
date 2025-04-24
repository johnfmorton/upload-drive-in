<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\FileUpload;
use App\Services\GoogleDriveManager;

class UploadController extends Controller
{
    protected GoogleDriveManager $drive_manager;

    /**
     * Construct the controller and apply middleware.
     *
     * @param  GoogleDriveManager  $drive_manager
     * @return void
     */
    public function __construct(GoogleDriveManager $drive_manager)
    {
        $this->middleware(['auth', 'employee']);
        $this->drive_manager = $drive_manager;
    }

    /**
     * Show the employee-specific upload page.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        $user = Auth::user();
        $current_folder_id = $user->google_drive_root_folder_id
            ?? config('cloud-storage.providers.google-drive.root_folder_id');
        $current_folder_name = $this->drive_manager->getFolderName(
            $user,
            $current_folder_id
        );

        return view('employee.upload-page', compact(
            'current_folder_id',
            'current_folder_name'
        ));
    }

    /**
     * Redirect the employee to Google's OAuth consent screen.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function connect()
    {
        return redirect(
            $this->drive_manager->getAuthUrl(Auth::user())
        );
    }

    /**
     * Handle the OAuth callback from Google Drive.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        $this->drive_manager->handleCallback(
            Auth::user(),
            $request->input('code')
        );

        return redirect()->route(
            'employee.upload.show',
            ['username' => Auth::user()->username]
        )
        ->with('success', __('messages.google_drive_connected'));
    }

    /**
     * Update the selected Google Drive root folder for the employee.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateFolder(Request $request)
    {
        $validated = $request->validate([
            'google_drive_root_folder_id' => ['required', 'string'],
        ]);

        $this->drive_manager->updateRootFolder(
            Auth::user(),
            $validated['google_drive_root_folder_id']
        );

        return redirect()->route(
            'employee.upload.show',
            ['username' => Auth::user()->username]
        )
        ->with('success', __('messages.save_root_folder'));
    }

    /**
     * Handle file uploads for the employee and dispatch Google Drive jobs.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB max per file
            'message' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $original_name = $file->getClientOriginalName();
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('uploads', $file, $filename);

                $upload = FileUpload::create([
                    'email' => $user->email,
                    'filename' => $filename,
                    'original_filename' => $original_name,
                    'google_drive_file_id' => '',
                    'message' => $request->input('message'),
                    'validation_method' => 'employee',
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);

                $this->drive_manager->dispatchUploadJob($upload, $user);
            }
        }

        return redirect()->route(
            'employee.upload.show',
            ['username' => $user->username]
        )
        ->with('success', __('messages.success_generic'));
    }
}
