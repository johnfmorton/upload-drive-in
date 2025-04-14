<?php

namespace App\Listeners;

use App\Events\FileUploaded;
use App\Mail\AdminUploadNotification;
use App\Mail\ClientUploadConfirmation;
use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendUploadNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(FileUploaded $event): void
    {
        // --- COMMENT OUT ORIGINAL LOGIC TO PREVENT PER-FILE EMAILS ---
        /*
        // Fetch models using IDs from the event
        $fileUpload = FileUpload::find($event->fileUploadId);
        $user = User::find($event->userId);

        // Check if models were found
        if (! $fileUpload || ! $user) {
             Log::error('Could not find FileUpload or User model in SendUploadNotifications listener', [
                 'fileUploadId' => $event->fileUploadId,
                 'userId' => $event->userId
             ]);
             return; // Stop processing if models not found
        }

        $admin_email = config('mail.admin_address');

        // --- Send Admin Notification ---
        if ($admin_email) {
            try {
                Log::info('Attempting to send admin notification...', ['admin_email' => $admin_email, 'file_upload_id' => $fileUpload->id]);
                Mail::to($admin_email)->send(new AdminUploadNotification($fileUpload, $user));
                Log::info('Admin notification sent successfully.', ['file_upload_id' => $fileUpload->id]);
            } catch (\Exception $e) {
                Log::error('Failed to send admin upload notification: ' . $e->getMessage());
            }
        } else {
            Log::warning('Admin email address not configured for upload notifications.');
        }

        // --- Client Confirmation Logic ---
        if ($user->receive_upload_notifications && $user->email) {
            try {
                Log::info('Attempting to send client confirmation...', ['user_email' => $user->email, 'file_upload_id' => $fileUpload->id]); // Add log
                $unsubscribe_url = URL::temporarySignedRoute(
                    'notifications.upload.unsubscribe',
                    now()->addDays(30),
                    ['user' => $user->id]
                );
                Mail::to($user->email)->send(new ClientUploadConfirmation($fileUpload, $unsubscribe_url));
                 Log::info('Client confirmation sent successfully.', ['file_upload_id' => $fileUpload->id]); // Add log
            } catch (\Exception $e) {
                Log::error("Failed to send client upload confirmation to {$user->email}: " . $e->getMessage());
            }
        }
        */
        // --- END OF COMMENTED OUT LOGIC ---

        // Optionally, log that the event was received but notifications are deferred
        Log::info('SendUploadNotifications listener handled FileUploaded event, but notifications are deferred to batch processing.', [
            'fileUploadId' => $event->fileUploadId,
            'userId' => $event->userId
        ]);
    }
}
