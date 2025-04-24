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
        // TODO: implement chunk upload association and dispatching logic

        return redirect()->route(
            'public.employee.upload.show',
            ['username' => $username]
        )
        ->with('success', __('messages.success_generic'));
    }
}
