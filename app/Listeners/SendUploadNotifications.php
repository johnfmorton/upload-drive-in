<?php

namespace App\Listeners;

use App\Events\FileUploaded;
use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * @deprecated This listener is kept for reference and development purposes.
 * Individual file upload notifications have been replaced by batch notifications.
 * See SendBatchUploadNotifications listener for the current implementation.
 * The handle method is intentionally empty and only logs the event receipt.
 */
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
        // --- Remove all original logic to ensure no per-file emails are sent ---

        // Log that the event was received but notifications are deferred.
        Log::info('SendUploadNotifications listener handled FileUploaded event, but notifications are deferred to batch processing.', [
            'fileUploadId' => $event->fileUploadId,
            'userId' => $event->userId
        ]);

        // Method is now intentionally empty to prevent any action.
    }
}
