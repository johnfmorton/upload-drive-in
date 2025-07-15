<?php

namespace App\Jobs;

use App\Models\FileUpload;
use App\Services\GoogleDriveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class UploadToGoogleDrive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The file upload record associated with this job.
     *
     * @var FileUpload
     */
    protected FileUpload $fileUpload;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param FileUpload $fileUpload The file upload model instance.
     */
    public function __construct(FileUpload $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    /**
     * Execute the job.
     * Uploads the file associated with the FileUpload record to Google Drive.
     *
     * @param GoogleDriveService $driveService Injected Google Drive service instance.
     * @return void
     * @throws Exception Throws exceptions on failure, allowing the queue worker to handle retries/failures.
     */
    public function handle(GoogleDriveService $driveService): void
    {
        $localPath = 'uploads/' . $this->fileUpload->filename;
        $email = $this->fileUpload->email;
        $originalFilename = $this->fileUpload->original_filename;
        $mimeType = $this->fileUpload->mime_type;
        $message = $this->fileUpload->message;

        Log::info('Starting Google Drive upload job', [
            'file_upload_id' => $this->fileUpload->id,
            'local_path' => $localPath,
            'email' => $email,
            'uploaded_by_user_id' => $this->fileUpload->uploaded_by_user_id
        ]);

        try {
            // 1. Verify local file exists
            if (!Storage::disk('public')->exists($localPath)) {
                Log::error('Local file missing for upload job.', ['path' => $localPath, 'file_upload_id' => $this->fileUpload->id]);
                // Fail the job permanently if the source file is gone
                $this->fail(new Exception("Source file not found in storage: {$localPath}"));
                return;
            }

            // 2. Determine which user's Google Drive to use
            $targetUser = null;
            
            // If this upload was made by an employee, use their Google Drive
            if ($this->fileUpload->uploaded_by_user_id) {
                $targetUser = \App\Models\User::find($this->fileUpload->uploaded_by_user_id);
                Log::info('Found target user for upload.', [
                    'target_user_id' => $targetUser?->id,
                    'is_employee' => $targetUser?->isEmployee(),
                    'has_drive_connected' => $targetUser?->hasGoogleDriveConnected()
                ]);
            }
            
            // Fallback to admin user if no employee specified
            if (!$targetUser) {
                $targetUser = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
                Log::info('Using admin user as fallback for upload.', ['admin_id' => $targetUser?->id]);
            }

            if (!$targetUser) {
                throw new Exception('No target user found for Google Drive upload');
            }

            // 3. Upload the file using the new method
            $description = "Uploaded by: " . $email . "\nMessage: " . ($message ?? 'No message');

            Log::info('Attempting file upload via GoogleDriveService.', [
                 'local_path' => $localPath,
                 'target_user_id' => $targetUser->id,
                 'client_email' => $email,
                 'target_name' => $originalFilename
            ]);

            $googleDriveFileId = $driveService->uploadFileForUser(
                $targetUser,
                $localPath,
                $email,
                $originalFilename,
                $mimeType,
                $description
            );

            // 5. Update the FileUpload record with the Google Drive ID
            $this->fileUpload->update(['google_drive_file_id' => $googleDriveFileId]);

            Log::info('Google Drive upload job completed successfully.', [
                'file_upload_id' => $this->fileUpload->id,
                'google_drive_file_id' => $googleDriveFileId,
                'target_user_id' => $targetUser->id
            ]);

            // 6. Delete the local file after successful upload
            try {
                Storage::disk('public')->delete($localPath);
                Log::info('Deleted local file after successful Google Drive upload.', ['path' => $localPath]);
            } catch (Exception $e) {
                Log::warning('Failed to delete local file after Google Drive upload.', ['path' => $localPath, 'error' => $e->getMessage()]);
            }

        } catch (Exception $e) {
            // Log the error with context
            Log::error('Google Drive upload job failed.', [
                'file_upload_id' => $this->fileUpload->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            // Re-throw the exception to let the queue worker handle retries/failure
            throw $e;
        }
    }
}
