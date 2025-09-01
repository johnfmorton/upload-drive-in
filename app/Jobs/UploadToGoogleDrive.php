<?php

namespace App\Jobs;

use App\Contracts\CloudStorageProviderInterface;
use App\Contracts\CloudStorageErrorHandlerInterface;
use App\Enums\CloudStorageErrorType;
use App\Exceptions\CloudStorageException;
use App\Models\FileUpload;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageLogService;
use App\Services\GoogleDriveProvider;
use App\Services\GoogleDriveErrorHandler;
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
     * The file upload record ID associated with this job.
     *
     * @var int
     */
    protected int $fileUploadId;

    /**
     * The number of times the job may be attempted.
     * This will be dynamically set based on error type
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     * This will be dynamically calculated based on error type
     *
     * @var array<int, int>
     */
    public array $backoff = [];

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
     * Determine which user's cloud storage to use for the upload.
     *
     * @param FileUpload $fileUpload
     * @param CloudStorageProviderInterface $provider
     * @return \App\Models\User|null
     */
    protected function determineTargetUser(FileUpload $fileUpload, CloudStorageProviderInterface $provider): ?\App\Models\User
    {
        $targetUser = null;
        
        // Priority 1: If client selected a specific company user (employee), use their cloud storage
        if ($fileUpload->company_user_id) {
            $targetUser = \App\Models\User::find($fileUpload->company_user_id);
            Log::info('Using selected company user for upload.', [
                'company_user_id' => $fileUpload->company_user_id,
                'target_user_id' => $targetUser?->id,
                'target_user_email' => $targetUser?->email,
                'has_valid_connection' => $targetUser ? $provider->hasValidConnection($targetUser) : false,
                'provider' => $provider->getProviderName(),
            ]);
        }
        
        // Priority 2: If this upload was made by an employee directly, use their cloud storage
        if (!$targetUser && $fileUpload->uploaded_by_user_id) {
            $targetUser = \App\Models\User::find($fileUpload->uploaded_by_user_id);
            Log::info('Using employee uploader for upload.', [
                'uploaded_by_user_id' => $fileUpload->uploaded_by_user_id,
                'target_user_id' => $targetUser?->id,
                'target_user_email' => $targetUser?->email,
                'has_valid_connection' => $targetUser ? $provider->hasValidConnection($targetUser) : false,
                'provider' => $provider->getProviderName(),
            ]);
        }
        
        // Priority 3: Only fall back to admin if target user doesn't have valid connection OR no target user found
        if (!$targetUser) {
            // No target user found at all, try admin
            $adminUser = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
            if ($adminUser && $provider->hasValidConnection($adminUser)) {
                $targetUser = $adminUser;
                Log::info('No target user found, using admin user as fallback.', [
                    'admin_id' => $targetUser->id,
                    'provider' => $provider->getProviderName(),
                ]);
            }
        } elseif (!$provider->hasValidConnection($targetUser)) {
            // Target user exists but doesn't have valid connection, try admin fallback
            Log::warning('Selected user does not have valid cloud storage connection, attempting admin fallback.', [
                'selected_user_id' => $targetUser->id,
                'selected_user_email' => $targetUser->email,
                'provider' => $provider->getProviderName(),
            ]);
            
            $adminUser = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
            if ($adminUser && $provider->hasValidConnection($adminUser)) {
                $targetUser = $adminUser;
                Log::info('Using admin user as fallback for upload.', [
                    'admin_id' => $targetUser->id,
                    'original_company_user_id' => $fileUpload->company_user_id,
                    'original_uploaded_by_user_id' => $fileUpload->uploaded_by_user_id,
                    'provider' => $provider->getProviderName(),
                ]);
            } else {
                Log::error('Selected user has no valid connection and admin also has no valid connection.', [
                    'selected_user_id' => $targetUser->id ?? null,
                    'admin_has_connection' => $adminUser ? $provider->hasValidConnection($adminUser) : false,
                    'provider' => $provider->getProviderName(),
                ]);
                return null;
            }
        } else {
            // Target user exists and has valid connection - use them!
            Log::info('Using target user with valid cloud storage connection.', [
                'target_user_id' => $targetUser->id,
                'target_user_email' => $targetUser->email,
                'provider' => $provider->getProviderName(),
            ]);
        }

        return $targetUser;
    }

    /**
     * Get detailed error information for logging and storage.
     *
     * @param Exception $exception
     * @param CloudStorageErrorType|null $errorType
     * @param array $context
     * @return array
     */
    protected function getErrorDetails(Exception $exception, ?CloudStorageErrorType $errorType = null, array $context = []): array
    {
        $details = [
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'timestamp' => now()->toISOString(),
            'attempt' => $this->attempts(),
            'classified_error_type' => $errorType?->value,
            'error_severity' => $errorType?->getSeverity(),
            'requires_user_intervention' => $errorType?->requiresUserIntervention() ?? false,
            'is_recoverable' => $errorType?->isRecoverable() ?? false,
        ];

        // Add CloudStorageException specific details
        if ($exception instanceof CloudStorageException) {
            $details['provider'] = $exception->getProvider();
            $details['cloud_storage_context'] = $exception->getContext();
        }

        // Add context information
        if (!empty($context)) {
            $details['context'] = $context;
        }

        return $details;
    }

    /**
     * Record error information in the database and update health status.
     *
     * @param FileUpload $fileUpload
     * @param Exception $exception
     * @param CloudStorageErrorType|null $errorType
     * @param CloudStorageHealthService $healthService
     * @param CloudStorageLogService $logService
     * @param array $context
     * @return void
     */
    protected function recordError(
        FileUpload $fileUpload, 
        Exception $exception, 
        ?CloudStorageErrorType $errorType,
        CloudStorageHealthService $healthService,
        CloudStorageLogService $logService,
        array $context = []
    ): void {
        $errorDetails = $this->getErrorDetails($exception, $errorType, $context);
        
        // Update FileUpload with cloud storage error information
        $fileUpload->update([
            'cloud_storage_provider' => 'google-drive',
            'cloud_storage_error_type' => $errorType?->value,
            'cloud_storage_error_context' => $errorDetails,
            'connection_health_at_failure' => now(),
            'last_error' => $exception->getMessage(),
            'error_details' => $errorDetails,
            'last_processed_at' => now(),
        ]);

        // Update health status if we have a target user
        if (isset($context['target_user_id'])) {
            $targetUser = \App\Models\User::find($context['target_user_id']);
            if ($targetUser && $errorType) {
                $healthService->markConnectionAsUnhealthy(
                    $targetUser, 
                    'google-drive', 
                    $exception->getMessage(),
                    $errorType
                );
            }
        }

        // Log retry decision if this isn't the final failure
        if (isset($context['target_user_id']) && !($context['final_failure'] ?? false)) {
            $targetUser = \App\Models\User::find($context['target_user_id']);
            if ($targetUser && $errorType) {
                $errorHandler = app(GoogleDriveErrorHandler::class);
                $shouldRetry = $errorHandler->shouldRetry($errorType, $this->attempts());
                $retryDelay = $shouldRetry ? $errorHandler->getRetryDelay($errorType, $this->attempts()) : null;
                
                $logService->logRetryDecision(
                    $context['operation_id'] ?? 'job_' . $this->fileUploadId,
                    'upload_job',
                    'google-drive',
                    $targetUser,
                    $errorType,
                    $this->attempts(),
                    $shouldRetry,
                    $retryDelay,
                    [
                        'file_upload_id' => $fileUpload->id,
                        'filename' => $fileUpload->original_filename,
                        'client_email' => $fileUpload->email,
                    ]
                );
            }
        }
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

        // Get error handler and health service for final failure processing
        $errorHandler = app(GoogleDriveErrorHandler::class);
        $healthService = app(CloudStorageHealthService::class);
        
        $errorType = null;
        if ($exception instanceof CloudStorageException) {
            $errorType = $exception->getErrorType();
        } else {
            $errorType = $errorHandler->classifyError($exception);
        }

        // Get log service for final failure processing
        $logService = app(CloudStorageLogService::class);
        
        // Record the final failure with enhanced context
        $this->recordError($fileUpload, $exception, $errorType, $healthService, $logService, [
            'final_failure' => true,
            'total_attempts' => $this->attempts(),
            'target_user_id' => $fileUpload->company_user_id ?? $fileUpload->uploaded_by_user_id,
        ]);

        Log::error('Cloud storage upload job permanently failed after all retries', [
            'file_upload_id' => $fileUpload->id,
            'provider' => 'google-drive',
            'total_attempts' => $this->attempts(),
            'error_type' => $errorType?->value,
            'error_severity' => $errorType?->getSeverity(),
            'requires_user_intervention' => $errorType?->requiresUserIntervention(),
            'error' => $exception->getMessage(),
            'exception_type' => get_class($exception),
        ]);
    }

    /**
     * Execute the job.
     * Uploads the file associated with the FileUpload record to cloud storage using provider interface.
     *
     * @param CloudStorageProviderInterface $provider Injected cloud storage provider (Google Drive)
     * @param CloudStorageErrorHandlerInterface $errorHandler Injected error handler
     * @param CloudStorageHealthService $healthService Injected health monitoring service
     * @param CloudStorageLogService $logService Injected logging service
     * @return void
     * @throws Exception Throws exceptions on failure, allowing the queue worker to handle retries/failures.
     */
    public function handle(
        CloudStorageProviderInterface $provider,
        CloudStorageErrorHandlerInterface $errorHandler,
        CloudStorageHealthService $healthService,
        CloudStorageLogService $logService
    ): void
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

        Log::info('Starting cloud storage upload job', [
            'file_upload_id' => $fileUpload->id,
            'provider' => $provider->getProviderName(),
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
                
                // Classify as file not found error and record
                $errorType = CloudStorageErrorType::FILE_NOT_FOUND;
                $this->recordError($fileUpload, $error, $errorType, $healthService, $logService, [
                    'error_category' => 'missing_file',
                    'permanent_failure' => true,
                ]);
                
                $this->fail($error);
                return;
            }

            // 2. Determine which user's cloud storage to use
            $targetUser = $this->determineTargetUser($fileUpload, $provider);
            
            if (!$targetUser) {
                $error = new Exception('No target user found for cloud storage upload');
                $errorType = CloudStorageErrorType::INVALID_CREDENTIALS;
                $this->recordError($fileUpload, $error, $errorType, $healthService, $logService, [
                    'error_category' => 'configuration',
                    'permanent_failure' => true,
                ]);
                throw $error;
            }

            // 3. Upload the file using the provider interface
            $description = "Uploaded by: " . $email . "\nMessage: " . ($message ?? 'No message');
            $metadata = [
                'original_filename' => $originalFilename,
                'mime_type' => $mimeType,
                'description' => $description,
                'client_email' => $email,
                'message' => $message,
            ];

            Log::info('Attempting file upload via cloud storage provider.', [
                 'provider' => $provider->getProviderName(),
                 'local_path' => $localPath,
                 'target_user_id' => $targetUser->id,
                 'client_email' => $email,
                 'target_name' => $originalFilename,
                 'attempt' => $this->attempts(),
            ]);

            $cloudFileId = $provider->uploadFile(
                $targetUser,
                $localPath,
                $email, // target path (client email for folder organization)
                $metadata
            );

            // 4. Update the FileUpload record with the cloud storage ID and clear error info
            $fileUpload->update([
                'google_drive_file_id' => $cloudFileId, // Keep existing field name for backward compatibility
                'cloud_storage_provider' => $provider->getProviderName(),
                'cloud_storage_error_type' => null,
                'cloud_storage_error_context' => null,
                'connection_health_at_failure' => null,
                'last_error' => null,
                'error_details' => null,
                'last_processed_at' => now(),
            ]);

            // Record successful operation in health monitoring
            $healthService->recordSuccessfulOperation($targetUser, $provider->getProviderName(), [
                'operation' => 'upload',
                'file_id' => $cloudFileId,
                'file_name' => $originalFilename,
                'client_email' => $email,
            ]);

            Log::info('Cloud storage upload job completed successfully.', [
                'file_upload_id' => $fileUpload->id,
                'provider' => $provider->getProviderName(),
                'cloud_file_id' => $cloudFileId,
                'target_user_id' => $targetUser->id,
                'total_attempts' => $this->attempts(),
            ]);

            // 5. Delete the local file after successful upload
            try {
                Storage::disk('public')->delete($localPath);
                Log::info('Deleted local file after successful cloud storage upload.', [
                    'path' => $localPath,
                    'provider' => $provider->getProviderName()
                ]);
            } catch (Exception $e) {
                Log::warning('Failed to delete local file after cloud storage upload.', [
                    'path' => $localPath, 
                    'provider' => $provider->getProviderName(),
                    'error' => $e->getMessage()
                ]);
            }

        } catch (Exception $e) {
            // Classify the error using the error handler
            $errorType = null;
            if ($e instanceof CloudStorageException) {
                $errorType = $e->getErrorType();
            } else {
                $errorType = $errorHandler->classifyError($e);
            }

            // Record detailed error information with enhanced context
            $context = [
                'local_path' => $localPath,
                'email' => $email,
                'target_user_id' => $targetUser->id ?? null,
                'original_filename' => $originalFilename,
                'mime_type' => $mimeType,
                'provider' => $provider->getProviderName(),
            ];
            
            $this->recordError($fileUpload, $e, $errorType, $healthService, $logService, $context);

            // Determine retry logic based on error handler
            $shouldRetry = $errorHandler->shouldRetry($errorType, $this->attempts());
            $maxAttempts = $errorHandler->getMaxRetryAttempts($errorType);
            
            // Update job tries based on error type
            if ($maxAttempts > 0) {
                $this->tries = min($maxAttempts + 1, $this->tries); // +1 because attempts start at 1
            }

            // Calculate retry delay if we should retry
            if ($shouldRetry) {
                $retryDelay = $errorHandler->getRetryDelay($errorType, $this->attempts());
                $this->backoff = [$retryDelay];
            }

            Log::error('Cloud storage upload job failed with enhanced error handling.', [
                'file_upload_id' => $fileUpload->id,
                'provider' => $provider->getProviderName(),
                'email' => $email,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'classified_error_type' => $errorType->value,
                'error_severity' => $errorType->getSeverity(),
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
                'should_retry' => $shouldRetry,
                'requires_user_intervention' => $errorType->requiresUserIntervention(),
                'retry_delay' => $shouldRetry ? $errorHandler->getRetryDelay($errorType, $this->attempts()) : null,
            ]);

            // For errors that don't allow retries, fail immediately
            if (!$shouldRetry) {
                Log::warning('Error type does not allow retries, failing job immediately', [
                    'file_upload_id' => $fileUpload->id,
                    'error_type' => $errorType->value,
                    'requires_user_intervention' => $errorType->requiresUserIntervention(),
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
