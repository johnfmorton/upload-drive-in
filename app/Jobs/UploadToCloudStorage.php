<?php

namespace App\Jobs;

use App\Models\FileUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class UploadToCloudStorage implements ShouldQueue
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
     * @param FileUpload $fileUpload The file upload model instance
     */
    public function __construct(FileUpload $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    /**
     * Execute the job.
     * Uploads the file associated with the FileUpload record to the configured cloud storage provider.
     *
     * @return void
     * @throws Exception Throws exceptions on failure, allowing the queue worker to handle retries/failures
     */
    public function handle(): void
    {
        $localPath = 'uploads/' . $this->fileUpload->filename;
        $email = $this->fileUpload->email;
        $originalFilename = $this->fileUpload->original_filename;
        $mimeType = $this->fileUpload->mime_type;
        $message = $this->fileUpload->message;

        Log::info('Starting cloud storage upload job', [
            'file_upload_id' => $this->fileUpload->id,
            'provider' => $this->fileUpload->storage_provider,
            'local_path' => $localPath,
            'email' => $email
        ]);

        try {
            // Get the appropriate storage provider
            $provider = $this->fileUpload->getStorageProvider();

            // Check file size limits
            if (!$this->fileUpload->isFileSizeWithinProviderLimits()) {
                $maxSize = $this->fileUpload->getProviderMaxFileSize();
                throw new Exception("File size exceeds provider limit of {$maxSize}");
            }

            // Get or Create User Folder
            Log::info('Getting or creating user folder.', ['email' => $email, 'provider' => $this->fileUpload->storage_provider]);
            $userFolderId = $provider->getOrCreateUserFolderId($email);
            Log::info('Obtained user folder ID.', ['email' => $email, 'folder_id' => $userFolderId]);

            // Upload the file
            $description = "Uploaded by: " . $email . "\nMessage: " . ($message ?? 'No message');

            Log::info('Attempting file upload.', [
                'local_path' => $localPath,
                'folder_id' => $userFolderId,
                'target_name' => $originalFilename
            ]);

            $providerFileId = $provider->uploadFile(
                $localPath,
                $userFolderId,
                $originalFilename,
                $mimeType,
                $description
            );

            // Update the FileUpload record with the provider's file ID
            $this->fileUpload->update(['provider_file_id' => $providerFileId]);

            Log::info('Cloud storage upload job completed successfully.', [
                'file_upload_id' => $this->fileUpload->id,
                'provider_file_id' => $providerFileId,
                'user_folder_id' => $userFolderId
            ]);

            // Optional: Delete the local file after successful upload
            // Uncomment if you want this behavior:
            // try {
            //     Storage::disk('public')->delete($localPath);
            //     Log::info('Deleted local file after successful upload.', ['path' => $localPath]);
            // } catch (Exception $e) {
            //     Log::warning('Failed to delete local file after upload.', [
            //         'path' => $localPath,
            //         'error' => $e->getMessage()
            //     ]);
            // }

        } catch (Exception $e) {
            Log::error('Cloud storage upload job failed.', [
                'file_upload_id' => $this->fileUpload->id,
                'provider' => $this->fileUpload->storage_provider,
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
