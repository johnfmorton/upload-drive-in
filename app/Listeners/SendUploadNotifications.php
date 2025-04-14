<?php

namespace App\Listeners;

use App\Events\FileUploaded;
use App\Mail\AdminUploadNotification;
use App\Mail\ClientUploadConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendUploadNotifications
{
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
        $file = $event->file;
        $user = $event->user;
        $admin_email = config('mail.admin_address');

        // --- Send Admin Notification ---
        if ($admin_email) {
            try {
                Mail::to($admin_email)->send(new AdminUploadNotification($file, $user));
            } catch (\Exception $e) {
                Log::error('Failed to send admin upload notification: ' . $e->getMessage());
            }
        } else {
            Log::warning('Admin email address not configured for upload notifications.');
        }

        // --- Send Client Confirmation ---
        if ($user->receive_upload_notifications && $user->email) {
            try {
                $unsubscribe_url = URL::temporarySignedRoute(
                    'notifications.upload.unsubscribe',
                    now()->addDays(30),
                    ['user' => $user->id]
                );

                Mail::to($user->email)->send(new ClientUploadConfirmation($file, $unsubscribe_url));
            } catch (\Exception $e) {
                Log::error("Failed to send client upload confirmation to {$user->email}: " . $e->getMessage());
            }
        }
    }
}
