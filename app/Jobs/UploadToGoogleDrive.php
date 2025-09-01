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
use Google\Service\Exception as GoogleServiceException;
use Google\Exception as GoogleException;

class UploadToGoogleDrive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The file upload record ID associated with this job.
     *
     * @var int
     */
    protected int $fileUploadId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     * Uses exponential backoff: 1 minute, 5 minutes, 15 minutes
     *
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    /**
     * Create a new job instance.
     *
     * @param FileUpload $fileUpload The file upload model instance.
     */
    public function __construct(FileUpload $fileUpload)
    {
        $this->fileUploadId = $fileUpload->id;
    }

    /**
     * Classify an error as transient or permanent.
     *
     * @param Exception $exception
     * @return bool True if transient (should retry), false if permanent
     */
    protected function isTransientError(Exception $exception): bool
    {
        // Google API Service exceptions
        if ($exception instanceof GoogleServiceException) {
            $code = $exception->getCode();
            
            // Transient errors that should be retried
            $transientCodes = [
                429, // Rate limit exceeded
                500, // Internal server error
                502, // Bad gateway
                503, // Service unavailable
                504, // Gateway timeout
            ];
            
            return in_array($code, $transientCodes);
        }

        // Google client exceptions (network issues, etc.)
        if ($exception instanceof GoogleException) {
            return true; // Most Google client exceptions are transient
        }

        // Network-related errors
        $message = strtolower($exception->getMessage());
        $transientMessages = [
            'connection timed out',
            'connection refused',
            'network is unreachable',
            'temporary failure',
            'timeout',
            'ssl connection error',
            'curl error',
        ];

        foreach ($transientMessages as $transientMessage) {
            if (str_contains($message, $transientMessage)) {
                return true;
            }
        }

        // Default to permanent error
        return false;
    }

    /**
     * Get detailed error information for logging and storage.
     *
     * @param Exception $exception
     * @param array $context
     * @return array
     */
    protected function getErrorDetails(Exception $exception, array $context = []): array
    {
        $details = [
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'timestamp' => now()->toISOString(),
            'attempt' => $this->attempts(),
            'is_transient' => $this->isTransientError($exception),
        ];

        // Add Google API specific details
        if ($exception instanceof GoogleServiceException) {
            $details['google_api_errors'] = $exception->getErrors();
            $details['http_status'] = $exception->getCode();
        }

        // Add context information
        if (!empty($context)) {
            $details['context'] = $context;
        }

        return $details;
    }

    /**
     * Record error information in the database.
     *
     * @param FileUpload $fileUpload
     * @param Exception $exception
     * @param array $context
     * @return void
     */
    protected function recordError(FileUpload $fileUpload, Exception $exception, array $context = []): void
    {
        $errorDetails = $this->getErrorDetails($exception, $context);
        
        $fileUpload->updateRecoveryStatus(
            $exception->getMessage(),
            $errorDetails
        );

        Log::error('Google Drive upload job failed with detailed error information', [
            'file_upload_id' => $fileUpload->id,
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
            'is_transient' => $errorDetails['is_transient'],
            'will_retry' => $errorDetails['is_transient'] && $this->attempts() < $this->tries,
            'error_details' => $errorDetails,
        ]);
    }

    /**
     * Handle job failure.
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception): void
    {
        $fileUpload = FileUpload::find($this->fileUploadId);
        
        if (!$fileUpload) {
            Log::warning('FileUpload record no longer exists during failure handling', [
                'file_upload_id' => $this->fileUploadId,
                'error' => $exception->getMessage(),
            ]);
            return;
        }

        // Record the final failure
        $this->recordError($fileUpload, $exception, [
            'final_failure' => true,
            'total_attempts' => $this->attempts(),
        ]);

        Log::error('Google Drive upload job permanently failed after all retries', [
            'file_upload_id' => $fileUpload->id,
            'total_attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'error_type' => get_class($exception),
        ]);
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
        // Try to find the FileUpload record
        $fileUpload = FileUpload::find($this->fileUploadId);
        
        if (!$fileUpload) {
            Log::warning('FileUpload record no longer exists, skipping job', [
                'file_upload_id' => $this->fileUploadId
            ]);
            return;
        }

        // Update last processed timestamp
        $fileUpload->update(['last_processed_at' => now()]);

        $localPath = 'uploads/' . $fileUpload->filename;
        $email = $fileUpload->email;
        $originalFilename = $fileUpload->original_filename;
        $mimeType = $fileUpload->mime_type;
        $message = $fileUpload->message;

        Log::info('Starting Google Drive upload job', [
            'file_upload_id' => $fileUpload->id,
            'local_path' => $localPath,
            'email' => $email,
            'uploaded_by_user_id' => $fileUpload->uploaded_by_user_id,
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
        ]);

        try {
            // 1. Verify local file exists
            if (!Storage::disk('public')->exists($localPath)) {
                $error = new Exception("Source file not found in storage: {$localPath}");
                Log::error('Local file missing for upload job.', [
                    'path' => $localPath, 
                    'file_upload_id' => $fileUpload->id,
                    'error' => $error->getMessage(),
                ]);
                
                // Record error and fail permanently - missing files are not recoverable
                $this->recordError($fileUpload, $error, [
                    'error_category' => 'missing_file',
                    'permanent_failure' => true,
                ]);
                
                $this->fail($error);
                return;
            }

            // 2. Determine which user's Google Drive to use
            $targetUser = null;
            
            // Priority 1: If client selected a specific company user (employee), use their Google Drive
            if ($fileUpload->company_user_id) {
                $targetUser = \App\Models\User::find($fileUpload->company_user_id);
                Log::info('Using selected company user for upload.', [
                    'company_user_id' => $fileUpload->company_user_id,
                    'target_user_id' => $targetUser?->id,
                    'target_user_email' => $targetUser?->email,
                    'has_drive_connected' => $targetUser?->hasGoogleDriveConnected()
                ]);
            }
            
            // Priority 2: If this upload was made by an employee directly, use their Google Drive
            if (!$targetUser && $fileUpload->uploaded_by_user_id) {
                $targetUser = \App\Models\User::find($fileUpload->uploaded_by_user_id);
                Log::info('Using employee uploader for upload.', [
                    'uploaded_by_user_id' => $fileUpload->uploaded_by_user_id,
                    'target_user_id' => $targetUser?->id,
                    'target_user_email' => $targetUser?->email,
                    'has_drive_connected' => $targetUser?->hasGoogleDriveConnected()
                ]);
            }
            
            // Priority 3: Only fall back to admin if target user doesn't have Google Drive OR no target user found
            if (!$targetUser) {
                // No target user found at all, try admin
                $adminUser = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
                if ($adminUser && $adminUser->hasGoogleDriveConnected()) {
                    $targetUser = $adminUser;
                    Log::info('No target user found, using admin user as fallback.', [
                        'admin_id' => $targetUser->id
                    ]);
                }
            } elseif (!$targetUser->hasGoogleDriveConnected()) {
                // Target user exists but doesn't have Google Drive, try admin fallback
                Log::warning('Selected user does not have Google Drive connected, attempting admin fallback.', [
                    'selected_user_id' => $targetUser->id,
                    'selected_user_email' => $targetUser->email
                ]);
                
                $adminUser = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
                if ($adminUser && $adminUser->hasGoogleDriveConnected()) {
                    $targetUser = $adminUser;
                    Log::info('Using admin user as fallback for upload.', [
                        'admin_id' => $targetUser->id,
                        'original_company_user_id' => $fileUpload->company_user_id,
                        'original_uploaded_by_user_id' => $fileUpload->uploaded_by_user_id
                    ]);
                } else {
                    $error = new Exception('No user with Google Drive connection available for upload');
                    Log::error('Selected user has no Google Drive and admin also has no Google Drive connection.');
                    
                    // This is a configuration issue, not a transient error
                    $this->recordError($fileUpload, $error, [
                        'error_category' => 'configuration',
                        'permanent_failure' => true,
                        'selected_user_id' => $targetUser->id ?? null,
                        'admin_has_drive' => false,
                    ]);
                    
                    throw $error;
                }
            } else {
                // Target user exists and has Google Drive - use them!
                Log::info('Using target user with Google Drive connection.', [
                    'target_user_id' => $targetUser->id,
                    'target_user_email' => $targetUser->email
                ]);
            }

            if (!$targetUser) {
                $error = new Exception('No target user found for Google Drive upload');
                $this->recordError($fileUpload, $error, [
                    'error_category' => 'configuration',
                    'permanent_failure' => true,
                ]);
                throw $error;
            }

            // 3. Upload the file using the new method
            $description = "Uploaded by: " . $email . "\nMessage: " . ($message ?? 'No message');

            Log::info('Attempting file upload via GoogleDriveService.', [
                 'local_path' => $localPath,
                 'target_user_id' => $targetUser->id,
                 'client_email' => $email,
                 'target_name' => $originalFilename,
                 'attempt' => $this->attempts(),
            ]);

            $googleDriveFileId = $driveService->uploadFileForUser(
                $targetUser,
                $localPath,
                $email,
                $originalFilename,
                $mimeType,
                $description
            );

            // 4. Update the FileUpload record with the Google Drive ID and clear error info
            $fileUpload->update([
                'google_drive_file_id' => $googleDriveFileId,
                'last_error' => null,
                'error_details' => null,
                'last_processed_at' => now(),
            ]);

            Log::info('Google Drive upload job completed successfully.', [
                'file_upload_id' => $fileUpload->id,
                'google_drive_file_id' => $googleDriveFileId,
                'target_user_id' => $targetUser->id,
                'total_attempts' => $this->attempts(),
            ]);

            // 5. Delete the local file after successful upload
            try {
                Storage::disk('public')->delete($localPath);
                Log::info('Deleted local file after successful Google Drive upload.', ['path' => $localPath]);
            } catch (Exception $e) {
                Log::warning('Failed to delete local file after Google Drive upload.', [
                    'path' => $localPath, 
                    'error' => $e->getMessage()
                ]);
            }

        } catch (Exception $e) {
            // Record detailed error information
            $context = [
                'local_path' => $localPath,
                'email' => $email,
                'target_user_id' => $targetUser->id ?? null,
                'original_filename' => $originalFilename,
                'mime_type' => $mimeType,
            ];
            
            $this->recordError($fileUpload, $e, $context);

            // Determine if we should retry based on error type
            $isTransient = $this->isTransientError($e);
            $willRetry = $isTransient && $this->attempts() < $this->tries;

            Log::error('Google Drive upload job failed.', [
                'file_upload_id' => $fileUpload->id,
                'email' => $email,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
                'is_transient' => $isTransient,
                'will_retry' => $willRetry,
            ]);

            // For permanent errors, fail immediately
            if (!$isTransient) {
                Log::warning('Permanent error detected, failing job immediately', [
                    'file_upload_id' => $fileUpload->id,
                    'error' => $e->getMessage(),
                ]);
                $this->fail($e);
                return;
            }

            // Re-throw the exception to let the queue worker handle retries
            throw $e;
        }
    }
}
