<?php

namespace App\Listeners;

use App\Events\BatchUploadComplete;
use App\Mail\AdminBatchUploadNotification; // To be created
use App\Mail\ClientBatchUploadConfirmation; // To be created
use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendBatchUploadNotifications implements ShouldQueue
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
     *
     * @param BatchUploadComplete $event
     * @return void
     */
    public function handle(BatchUploadComplete $event): void
    {
        Log::info('SendBatchUploadNotifications handling BatchUploadComplete event.', [
            'userId' => $event->userId,
            'fileCount' => count($event->fileUploadIds)
        ]);

        $user = User::find($event->userId);
        $fileUploads = FileUpload::whereIn('id', $event->fileUploadIds)->get();

        if (!$user || $fileUploads->isEmpty()) {
            Log::error('Could not find User or FileUpload models for batch notification.', [
                'userId' => $event->userId,
                'fileUploadIds' => $event->fileUploadIds
            ]);
            return;
        }

        // --- Send Admin Notification ---
        $admin_email = config('mail.admin_address');
        if ($admin_email) {
            try {
                Log::info('Attempting to send admin batch notification.', ['admin_email' => $admin_email, 'user_id' => $user->id, 'file_count' => $fileUploads->count()]);
                Mail::to($admin_email)->send(new AdminBatchUploadNotification($fileUploads, $user)); // Mailable to be created
                Log::info('Admin batch notification queued successfully.');
            } catch (\Exception $e) {
                Log::error('Failed to queue admin batch upload notification: ' . $e->getMessage());
            }
        } else {
            Log::warning('Admin email address not configured for batch upload notifications.');
        }

        // --- Send Client Confirmation ---
        if ($user->receive_upload_notifications && $user->email) {
            try {
                 Log::info('Attempting to send client batch confirmation.', ['user_email' => $user->email, 'file_count' => $fileUploads->count()]);
                $unsubscribe_url = URL::temporarySignedRoute(
                    'notifications.upload.unsubscribe',
                    now()->addDays(30),
                    ['user' => $user->id]
                );
                Mail::to($user->email)->send(new ClientBatchUploadConfirmation($fileUploads, $unsubscribe_url)); // Mailable to be created
                Log::info('Client batch confirmation queued successfully.');
            } catch (\Exception $e) {
                Log::error("Failed to queue client batch upload confirmation to {$user->email}: " . $e->getMessage());
            }
        }
    }
}
