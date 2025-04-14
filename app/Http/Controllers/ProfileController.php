<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\EmailValidation;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailVerificationMail;
use Illuminate\Support\Facades\URL;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Storage;
use App\Models\FileUpload;
use App\Mail\AccountDeletionMail;
use App\Models\AccountDeletionRequest;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // <-- Log raw request data FIRST -->
        Log::debug('Raw Profile Update Request Data:', $request->all());

        // Get validated data
        $validated_data = $request->validated();

        // Ensure 'receive_upload_notifications' is set to false if not present in the request
        $validated_data['receive_upload_notifications'] = $request->has('receive_upload_notifications');

        // Fill the user model using the processed data
        $request->user()->fill($validated_data);

        // Reset email verification if the email was changed
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // Optional: Log before saving for confirmation
        Log::debug('Saving profile update', [
            'user_id' => $request->user()->id,
            'data_to_save' => $validated_data // Use the processed data for logging
        ]);

        // Save the user model
        $request->user()->save();

        // Redirect back to the profile edit page with a success status
        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            // Admin deletion logic remains the same
            $request->validateWithBag('userDeletion', [
                'password' => ['required', 'current_password'],
            ]);

            Auth::logout();
            $user->delete();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return Redirect::to('/');
        } else {
            try {
                // Client deletion - only send email
                $verificationCode = Str::random(32);

                Log::info('Initiating client account deletion process', [
                    'user_email' => $user->email
                ]);

                $validation = AccountDeletionRequest::updateOrCreate(
                    ['email' => $user->email],
                    [
                        'verification_code' => $verificationCode,
                        'expires_at' => now()->addHours(24)
                    ]
                );

                $verificationUrl = URL::temporarySignedRoute(
                    'profile.confirm-deletion',
                    now()->addHours(24),
                    [
                        'code' => $verificationCode,
                        'email' => $user->email
                    ]
                );

                Mail::to($user->email)->send(new AccountDeletionMail($verificationUrl));

                // Return to the same page with a status message
                return back()->with('deletion-requested', true);

            } catch (\Exception $e) {
                Log::error('Failed to process account deletion request', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return back()->withErrors(['userDeletion' => 'Failed to process deletion request. Please try again.']);
            }
        }
    }

    public function confirmDeletion(Request $request, string $code, string $email): RedirectResponse
    {
        try {
            if (!$request->hasValidSignature()) {
                Log::warning('Invalid or expired deletion confirmation URL accessed', ['email' => $email]);
                return redirect()->route('home')
                    ->with('error', 'The deletion confirmation link is invalid or has expired.');
            }

            $validation = AccountDeletionRequest::where('email', $email)
                ->where('verification_code', $code)
                ->where('expires_at', '>', now())
                ->first();

            if (!$validation) {
                Log::warning('Invalid deletion confirmation attempt', ['email' => $email]);
                return redirect()->route('home')
                    ->with('error', 'Invalid or expired verification link.');
            }

            $user = User::where('email', $email)->first();

            if (!$user || $user->isAdmin()) {
                Log::warning('Invalid user account in deletion confirmation', ['email' => $email]);
                return redirect()->route('home')
                    ->with('error', 'Invalid user account.');
            }

            // Get the Google Drive service
            $driveService = app(GoogleDriveService::class);

            // Process deletion with proper error handling
            DB::beginTransaction();
            try {
                // Delete files and clean up
                $this->deleteUserFiles($user, $driveService);

                // Delete the user
                $user->delete();

                // Delete the validation record
                $validation->delete();

                DB::commit();

                return redirect()->route('home')
                    ->with('status', 'Your account and all associated data have been permanently deleted.');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to delete user account during confirmation', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return redirect()->route('home')
                    ->with('error', 'An error occurred while deleting your account. Please try again or contact support.');
            }
        } catch (\Exception $e) {
            Log::error('Unexpected error in deletion confirmation', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home')
                ->with('error', 'An unexpected error occurred. Please try again or contact support.');
        }
    }

    private function deleteUserFiles(User $user, GoogleDriveService $driveService): void
    {
        // Get all file uploads for this user
        $fileUploads = FileUpload::where('email', $user->email)->get();

        foreach ($fileUploads as $fileUpload) {
            // Delete from Google Drive
            if ($fileUpload->google_drive_file_id) {
                try {
                    $driveService->deleteFile($fileUpload->google_drive_file_id);
                } catch (\Exception $e) {
                    Log::warning("Failed to delete Google Drive file", [
                        'file_id' => $fileUpload->google_drive_file_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Delete local file
            $localPath = 'uploads/' . $fileUpload->filename;
            if (Storage::disk('public')->exists($localPath)) {
                Storage::disk('public')->delete($localPath);
            }
        }

        // Delete Google Drive folder
        try {
            $userFolderId = $driveService->findUserFolderId($user->email);
            if ($userFolderId) {
                $driveService->deleteFolder($userFolderId);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to delete Google Drive folder", [
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
        }

        // Delete file upload records
        FileUpload::where('email', $user->email)->delete();
    }
}
