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
            'email' => $email
        ]);

        try {
            // 1. Verify local file exists
            if (!Storage::disk('public')->exists($localPath)) {
                Log::error('Local file missing for upload job.', ['path' => $localPath, 'file_upload_id' => $this->fileUpload->id]);
                // Fail the job permanently if the source file is gone
                $this->fail(new Exception("Source file not found in storage: {$localPath}"));
                return;
            }

            // 2. Get or Create User Folder using the service
            // The service handles finding or creating the folder based on the email
            Log::info('Getting or creating user folder on Google Drive.', ['email' => $email]);
            $userFolderId = $driveService->getOrCreateUserFolderId($email);
            Log::info('Obtained Google Drive user folder ID.', ['email' => $email, 'folder_id' => $userFolderId]);

            // 3. Upload the file using the service
            $description = "Uploaded by: " . $email . "\nMessage: " . ($message ?? 'No message');

            Log::info('Attempting file upload via GoogleDriveService.', [
                 'local_path' => $localPath,
                 'folder_id' => $userFolderId,
                 'target_name' => $originalFilename
            ]);

            $googleDriveFileId = $driveService->uploadFile(
                $localPath,
                $userFolderId,
                $originalFilename,
                $mimeType,
                $description
            );

            // 4. Update the FileUpload record with the Google Drive ID
            $this->fileUpload->update(['google_drive_file_id' => $googleDriveFileId]);

            Log::info('Google Drive upload job completed successfully.', [
                'file_upload_id' => $this->fileUpload->id,
                'google_drive_file_id' => $googleDriveFileId,
                'user_folder_id' => $userFolderId
            ]);

            // 5. Optional: Delete the local file after successful upload to save space
            // Uncomment the following lines if you want this behavior:
            // try {
            //     Storage::disk('public')->delete($localPath);
            //     Log::info('Deleted local file after successful Google Drive upload.', ['path' => $localPath]);
            // } catch (Exception $e) {
            //     Log::warning('Failed to delete local file after Google Drive upload.', ['path' => $localPath, 'error' => $e->getMessage()]);
            //     // Don't fail the job just because local deletion failed.
            // }

        } catch (Exception $e) {
            // Log the error with context
            Log::error('Google Drive upload job failed.', [
                'file_upload_id' => $this->fileUpload->id,
                'email' => $email,
                'error' => $e->getMessage(),
                // Only include trace in non-production environments or if essential for debugging
                // 'trace' => $e->getTraceAsString()
            ]);

            // Re-throw the exception to let the queue worker handle retries/failure
            throw $e;
        }
    }
}
