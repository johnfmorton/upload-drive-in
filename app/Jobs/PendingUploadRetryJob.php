<?php

namespace App\Jobs;

use App\Models\FileUpload;
use App\Services\CloudStorageManager;
use App\Services\CloudStorageHealthService;
use App\Services\TokenRenewalNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Job for retrying pending uploads after connection recovery.
 * This job is specifically designed for recovery scenarios and includes
 * enhanced error handling and notification logic.
 */
class PendingUploadRetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [30, 60, 120]; // 30s, 1min, 2min

    /**
     * Create a new job instance.
     */
    public function __construct(
        private FileUpload $fileUpload,
        private string $provider,
        private string $recoveryOperationId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        CloudStorageManager $storageManager,
        CloudStorageHealthService $healthService,
        TokenRenewalNotificationService $notificationService
    ): void {
        $operationId = uniqid('retry_', true);
        
        Log::info('Starting pending upload retry job', [
            'file_upload_id' => $this->fileUpload->id,
            'provider' => $this->provider,
            'recovery_operation_id' => $this->recoveryOperationId,
            'operation_id' => $operationId,
            'attempt' => $this->attempts(),
            'filename' => $this->fileUpload->original_filename
        ]);

        try {
            // 1. Verify the upload is still pending and can be retried
            if (!$this->shouldRetryUpload()) {
                Log::info('Upload no longer needs retry, skipping', [
                    'file_upload_id' => $this->fileUpload->id,
                    'operation_id' => $operationId,
                    'reason' => $this->getSkipReason()
                ]);
                return;
            }

            // 2. Increment recovery attempts counter
            $this->fileUpload->incrementRecoveryAttempts();

            // 3. Verify local file still exists
            if (!$this->fileUpload->localFileExists()) {
                Log::warning('Local file no longer exists for retry', [
                    'file_upload_id' => $this->fileUpload->id,
                    'operation_id' => $operationId,
                    'filename' => $this->fileUpload->filename
                ]);

                $this->fileUpload->updateRecoveryStatus(__('messages.recovery_local_file_no_longer_exists'), [
                    'recovery_failed' => true,
                    'reason' => 'missing_local_file',
                    'operation_id' => $operationId
                ]);
                return;
            }

            // 4. Determine target user for the upload
            $targetUser = $this->determineTargetUser();
            if (!$targetUser) {
                Log::error('No target user found for retry', [
                    'file_upload_id' => $this->fileUpload->id,
                    'operation_id' => $operationId
                ]);

                $this->fileUpload->updateRecoveryStatus(__('messages.recovery_no_target_user_found'), [
                    'recovery_failed' => true,
                    'reason' => 'no_target_user',
                    'operation_id' => $operationId
                ]);
                return;
            }

            // 5. Verify connection health before retry
            $healthStatus = $healthService->getHealthSummary($targetUser, $this->provider);
            if (!$healthStatus['is_healthy']) {
                Log::warning('Connection still unhealthy, deferring retry', [
                    'file_upload_id' => $this->fileUpload->id,
                    'operation_id' => $operationId,
                    'target_user_id' => $targetUser->id,
                    'health_status' => $healthStatus['status']
                ]);

                // Release the job back to the queue with delay
                $this->release(300); // 5 minutes
                return;
            }

            // 6. Clear any previous cloud storage error information
            $this->fileUpload->clearCloudStorageError();

            // 7. Dispatch the original upload job for retry
            Log::info('Dispatching original upload job for retry', [
                'file_upload_id' => $this->fileUpload->id,
                'operation_id' => $operationId,
                'target_user_id' => $targetUser->id,
                'recovery_operation_id' => $this->recoveryOperationId
            ]);

            // Use the original UploadToGoogleDrive job but with high priority
            UploadToGoogleDrive::dispatch($this->fileUpload)
                ->onQueue('high')
                ->delay(now()->addSeconds(5)); // Small delay to avoid overwhelming

            // 8. Update the file upload record
            $this->fileUpload->update([
                'last_processed_at' => now(),
                'retry_recommended_at' => null, // Clear retry recommendation
            ]);

            Log::info('Pending upload retry job completed successfully', [
                'file_upload_id' => $this->fileUpload->id,
                'operation_id' => $operationId,
                'recovery_operation_id' => $this->recoveryOperationId,
                'target_user_id' => $targetUser->id
            ]);

        } catch (Exception $e) {
            Log::error('Exception in pending upload retry job', [
                'file_upload_id' => $this->fileUpload->id,
                'operation_id' => $operationId,
                'recovery_operation_id' => $this->recoveryOperationId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'exception_class' => get_class($e)
            ]);

            // Update recovery status with error
            $this->fileUpload->updateRecoveryStatus($e->getMessage(), [
                'retry_job_failed' => true,
                'attempt' => $this->attempts(),
                'operation_id' => $operationId,
                'exception_class' => get_class($e)
            ]);

            throw $e; // Re-throw to trigger job retry mechanism
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Pending upload retry job permanently failed', [
            'file_upload_id' => $this->fileUpload->id,
            'provider' => $this->provider,
            'recovery_operation_id' => $this->recoveryOperationId,
            'total_attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'exception_class' => get_class($exception)
        ]);

        // Mark the upload as having failed recovery
        $this->fileUpload->updateRecoveryStatus(__('messages.recovery_retry_job_permanently_failed') . ': ' . $exception->getMessage(), [
            'recovery_permanently_failed' => true,
            'total_retry_attempts' => $this->attempts(),
            'final_error' => $exception->getMessage(),
            'final_exception_class' => get_class($exception),
            'recovery_operation_id' => $this->recoveryOperationId
        ]);

        // Try to notify relevant users about the permanent failure
        try {
            $targetUser = $this->determineTargetUser();
            if ($targetUser) {
                $notificationService = app(TokenRenewalNotificationService::class);
                
                // Send a generic failure notification
                // Note: We're using token refresh notification as a fallback since we don't have
                // a specific "upload retry failed" notification type
                $notificationService->sendRefreshFailureNotification(
                    $targetUser,
                    $this->provider,
                    \App\Enums\TokenRefreshErrorType::UNKNOWN_ERROR,
                    $this->attempts(),
                    __('messages.recovery_upload_retry_failed_for_file') . ": {$this->fileUpload->original_filename}"
                );
            }
        } catch (Exception $notificationError) {
            Log::error('Failed to send retry failure notification', [
                'file_upload_id' => $this->fileUpload->id,
                'notification_error' => $notificationError->getMessage()
            ]);
        }
    }

    /**
     * Check if the upload should be retried.
     */
    private function shouldRetryUpload(): bool
    {
        // Refresh the model to get latest state
        $this->fileUpload->refresh();

        // Don't retry if already uploaded
        if (!$this->fileUpload->isPending()) {
            return false;
        }

        // Don't retry if exceeded retry limits
        if (!$this->fileUpload->canBeRetried()) {
            return false;
        }

        // Don't retry if has non-recoverable error
        if ($this->fileUpload->hasCloudStorageError() && !$this->fileUpload->isCloudStorageErrorRecoverable()) {
            return false;
        }

        return true;
    }

    /**
     * Get the reason why upload retry was skipped.
     */
    private function getSkipReason(): string
    {
        $this->fileUpload->refresh();

        if (!$this->fileUpload->isPending()) {
            return 'already_uploaded';
        }

        if (!$this->fileUpload->canBeRetried()) {
            return 'exceeded_retry_limits';
        }

        if ($this->fileUpload->hasCloudStorageError() && !$this->fileUpload->isCloudStorageErrorRecoverable()) {
            return 'non_recoverable_error';
        }

        return 'unknown';
    }

    /**
     * Determine which user's cloud storage to use for the retry.
     */
    private function determineTargetUser(): ?\App\Models\User
    {
        // Use the same logic as the original upload job
        if ($this->fileUpload->company_user_id) {
            $user = \App\Models\User::find($this->fileUpload->company_user_id);
            if ($user) {
                return $user;
            }
        }

        if ($this->fileUpload->uploaded_by_user_id) {
            $user = \App\Models\User::find($this->fileUpload->uploaded_by_user_id);
            if ($user) {
                return $user;
            }
        }

        // Fallback to admin user
        return \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
    }

    /**
     * Get the unique identifier for this job.
     */
    public function uniqueId(): string
    {
        return "pending_upload_retry_{$this->fileUpload->id}_{$this->provider}";
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }
}