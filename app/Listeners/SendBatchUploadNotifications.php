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

        // Determine intended recipient users (employee/admin) from the batch (unique IDs)
        $recipientUserIds = [];
        foreach ($fileUploads as $upload) {
            $candidateId = $upload->company_user_id ?? $upload->uploaded_by_user_id;
            if (!$candidateId) {
                $fallbackAdmin = User::where('role', \App\Enums\UserRole::ADMIN)->value('id');
                $candidateId = $fallbackAdmin ?: null;
            }
            if ($candidateId) {
                $recipientUserIds[$candidateId] = true; // use map to ensure uniqueness
            }
        }
        $recipientUserIds = array_keys($recipientUserIds);

        // Send per-recipient notification using Eloquent collections
        foreach ($recipientUserIds as $recipient_user_id) {
            $recipient = User::find($recipient_user_id);
            if (!$recipient || !$recipient->email) {
                Log::warning('Skipping recipient without valid user/email for upload notification.', [
                    'recipient_user_id' => $recipient_user_id,
                ]);
                continue;
            }
            // Fetch uploads for this recipient as an Eloquent collection
            $uploadsForRecipient = FileUpload::whereIn('id', $event->fileUploadIds)
                ->where(function ($q) use ($recipient_user_id) {
                    $q->where('company_user_id', $recipient_user_id)
                      ->orWhere('uploaded_by_user_id', $recipient_user_id);
                })
                ->get();
            if ($uploadsForRecipient->isEmpty()) {
                continue;
            }
            try {
                Log::info('Sending batch upload notification to recipient.', [
                    'recipient_email' => $recipient->email,
                    'recipient_user_id' => $recipient->id,
                    'file_count' => $uploadsForRecipient->count(),
                    'uploader_user_id' => $user->id,
                ]);
                Mail::to($recipient->email)->send(new AdminBatchUploadNotification($uploadsForRecipient, $user));
            } catch (\Exception $e) {
                Log::error('Failed to send recipient batch upload notification: ' . $e->getMessage(), [
                    'recipient_user_id' => $recipient->id ?? null,
                ]);
            }
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
                // Build recipient names for client visibility
                $recipient_names = collect($recipientUserIds)
                    ->map(fn ($id) => optional(User::find($id))->name)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $recipient_names = collect($recipientUserIds)
                    ->map(fn ($id) => optional(User::find($id))->name)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $mailable = new ClientBatchUploadConfirmation($fileUploads, $unsubscribe_url);
                $mailable->recipientNames = $recipient_names;
                Mail::to($user->email)->send($mailable);
                Log::info('Client batch confirmation queued successfully.');
            } catch (\Exception $e) {
                Log::error("Failed to queue client batch upload confirmation to {$user->email}: " . $e->getMessage());
            }
        }
    }
}
