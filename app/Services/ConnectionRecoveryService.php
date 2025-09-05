<?php

namespace App\Services;

use App\Enums\CloudStorageErrorType;
use App\Enums\RecoveryStrategy;
use App\Enums\TokenRefreshErrorType;
use App\Jobs\PendingUploadRetryJob;
use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Exception;

/**
 * Service for automatically recovering from cloud storage connection issues
 * and retrying pending uploads when connection is restored.
 */
class ConnectionRecoveryService
{
    public function __construct(
        private CloudStorageManager $storageManager,
        private CloudStorageHealthService $healthService,
        private TokenRenewalNotificationService $notificationService,
        private RealTimeHealthValidator $healthValidator
    ) {}

    /**
     * Attempt automatic recovery from connection issues for a user and provider.
     *
     * @param User $user The user whose connection to recover
     * @param string $provider The cloud storage provider name
     * @return RecoveryResult The result of the recovery attempt
     */
    public function attemptAutomaticRecovery(User $user, string $provider): RecoveryResult
    {
        $operationId = uniqid('recovery_', true);
        
        Log::info('Starting automatic connection recovery', [
            'user_id' => $user->id,
            'provider' => $provider,
            'operation_id' => $operationId
        ]);

        try {
            // 1. Validate current connection health
            $healthStatus = $this->healthValidator->validateConnectionHealth($user, $provider);
            
            if ($healthStatus->isHealthy()) {
                Log::info('Connection is already healthy, no recovery needed', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'operation_id' => $operationId
                ]);
                
                // Connection is healthy, retry pending uploads
                $this->retryPendingUploads($user, $provider);
                
                return RecoveryResult::success(
                    __('messages.recovery_connection_healthy'),
                    RecoveryStrategy::NO_ACTION_NEEDED
                );
            }

            // 2. Determine recovery strategy based on error type
            $errorType = $this->determineErrorType($user, $provider);
            $strategy = $this->determineRecoveryStrategy($errorType);
            
            Log::info('Determined recovery strategy', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId,
                'error_type' => $errorType?->value,
                'strategy' => $strategy->value
            ]);

            // 3. Execute recovery strategy
            $recoveryResult = $this->executeRecoveryStrategy($user, $provider, $strategy, $errorType, $operationId);
            
            // 4. If recovery was successful, retry pending uploads
            if ($recoveryResult->isSuccessful()) {
                Log::info('Recovery successful, retrying pending uploads', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'operation_id' => $operationId,
                    'strategy' => $strategy->value
                ]);
                
                $this->retryPendingUploads($user, $provider);
                
                // Send connection restored notification
                $this->notificationService->sendConnectionRestoredNotification($user, $provider);
            } else {
                Log::warning('Recovery failed', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'operation_id' => $operationId,
                    'strategy' => $strategy->value,
                    'error' => $recoveryResult->getErrorMessage()
                ]);
                
                // Send recovery failure notification if appropriate
                $this->handleRecoveryFailure($user, $provider, $errorType, $recoveryResult->getException(), $operationId);
            }

            return $recoveryResult;

        } catch (Exception $e) {
            Log::error('Exception during automatic recovery', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e)
            ]);

            return RecoveryResult::failure(
                __('messages.recovery_failed_due_to_exception') . ': ' . $e->getMessage(),
                RecoveryStrategy::UNKNOWN,
                $e
            );
        }
    }

    /**
     * Retry pending uploads for a user and provider when connection is restored.
     *
     * @param User $user The user whose uploads to retry
     * @param string $provider The cloud storage provider name
     * @return void
     */
    public function retryPendingUploads(User $user, string $provider): void
    {
        $operationId = uniqid('retry_', true);
        
        Log::info('Starting pending upload retry', [
            'user_id' => $user->id,
            'provider' => $provider,
            'operation_id' => $operationId
        ]);

        try {
            // Find pending uploads for this user and provider
            $pendingUploads = $this->findPendingUploads($user, $provider);
            
            if ($pendingUploads->isEmpty()) {
                Log::info('No pending uploads found for retry', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'operation_id' => $operationId
                ]);
                return;
            }

            Log::info('Found pending uploads for retry', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId,
                'pending_count' => $pendingUploads->count()
            ]);

            // Process uploads in batches to avoid overwhelming the system
            $batchSize = config('upload-recovery.retry_batch_size', 10);
            $delayBetweenBatches = config('upload-recovery.retry_batch_delay', 30); // seconds
            
            $pendingUploads->chunk($batchSize)->each(function ($batch, $batchIndex) use ($provider, $operationId, $delayBetweenBatches) {
                $delay = $batchIndex * $delayBetweenBatches;
                
                Log::info('Scheduling upload retry batch', [
                    'operation_id' => $operationId,
                    'batch_index' => $batchIndex,
                    'batch_size' => $batch->count(),
                    'delay_seconds' => $delay
                ]);

                foreach ($batch as $upload) {
                    // Verify file still exists locally before queuing retry
                    if (!$upload->localFileExists()) {
                        Log::warning('Skipping retry for upload with missing local file', [
                            'file_upload_id' => $upload->id,
                            'filename' => $upload->filename,
                            'operation_id' => $operationId
                        ]);
                        
                        $upload->updateRecoveryStatus(__('messages.recovery_local_file_no_longer_exists'), [
                            'recovery_skipped' => true,
                            'reason' => 'missing_local_file'
                        ]);
                        continue;
                    }

                    // Check if upload has exceeded retry limits
                    if (!$upload->canBeRetried()) {
                        Log::warning('Skipping retry for upload that exceeded retry limits', [
                            'file_upload_id' => $upload->id,
                            'retry_count' => $upload->retry_count,
                            'recovery_attempts' => $upload->recovery_attempts,
                            'operation_id' => $operationId
                        ]);
                        continue;
                    }

                    // Queue the retry job with appropriate delay
                    PendingUploadRetryJob::dispatch($upload, $provider, $operationId)
                        ->delay(now()->addSeconds($delay))
                        ->onQueue('recovery');
                    
                    Log::info('Queued pending upload for retry', [
                        'file_upload_id' => $upload->id,
                        'filename' => $upload->original_filename,
                        'delay_seconds' => $delay,
                        'operation_id' => $operationId
                    ]);
                }
            });

        } catch (Exception $e) {
            Log::error('Exception during pending upload retry', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e)
            ]);
        }
    }

    /**
     * Find pending uploads for a user and provider that can be retried.
     *
     * @param User $user The user whose uploads to find
     * @param string $provider The cloud storage provider name
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findPendingUploads(User $user, string $provider): \Illuminate\Database\Eloquent\Collection
    {
        // Find uploads that are:
        // 1. Pending (not uploaded to cloud storage)
        // 2. Associated with this user (either as company user or uploader)
        // 3. For the specified provider
        // 4. Can still be retried (haven't exceeded limits)
        // 5. Have recoverable errors or no specific error type
        
        return FileUpload::query()
            ->pending()
            ->where(function ($query) use ($user) {
                $query->where('company_user_id', $user->id)
                      ->orWhere('uploaded_by_user_id', $user->id);
            })
            ->where('storage_provider', $provider)
            ->where(function ($query) {
                // Include uploads with no cloud storage error or recoverable errors
                $query->whereNull('cloud_storage_error_type')
                      ->orWhereIn('cloud_storage_error_type', $this->getRecoverableErrorTypes());
            })
            ->recoverable() // Uses the scope from FileUpload model
            ->orderBy('created_at', 'asc') // Retry oldest uploads first
            ->get();
    }

    /**
     * Get list of recoverable cloud storage error types.
     *
     * @return array
     */
    private function getRecoverableErrorTypes(): array
    {
        return collect(CloudStorageErrorType::cases())
            ->filter(fn($type) => $type->isRecoverable())
            ->map(fn($type) => $type->value)
            ->toArray();
    }

    /**
     * Determine the error type causing connection issues.
     *
     * @param User $user The user whose connection to check
     * @param string $provider The cloud storage provider name
     * @return CloudStorageErrorType|null
     */
    private function determineErrorType(User $user, string $provider): ?CloudStorageErrorType
    {
        try {
            // Check the most recent health status for this user/provider
            $healthStatus = $this->healthService->getHealthStatus($user, $provider);
            
            if ($healthStatus && $healthStatus->last_error) {
                // Try to get error handler to classify the error
                $providerInstance = $this->storageManager->getUserProvider($user);
                $errorHandler = $this->storageManager->getErrorHandler($providerInstance->getProviderName());
                
                // Create a generic exception from the stored error message
                $exception = new Exception($healthStatus->last_error);
                return $errorHandler->classifyError($exception);
            }

            // Check recent failed uploads for error patterns
            $recentFailedUpload = FileUpload::query()
                ->where(function ($query) use ($user) {
                    $query->where('company_user_id', $user->id)
                          ->orWhere('uploaded_by_user_id', $user->id);
                })
                ->where('storage_provider', $provider)
                ->whereNotNull('cloud_storage_error_type')
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($recentFailedUpload) {
                return $recentFailedUpload->getCloudStorageErrorType();
            }

            return null;

        } catch (Exception $e) {
            Log::warning('Failed to determine error type for recovery', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Determine the appropriate recovery strategy based on error type.
     *
     * @param CloudStorageErrorType|null $errorType The error type to recover from
     * @return RecoveryStrategy
     */
    private function determineRecoveryStrategy(?CloudStorageErrorType $errorType): RecoveryStrategy
    {
        if (!$errorType) {
            return RecoveryStrategy::HEALTH_CHECK_RETRY;
        }

        return match ($errorType) {
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::INVALID_CREDENTIALS => RecoveryStrategy::TOKEN_REFRESH,
            
            CloudStorageErrorType::NETWORK_ERROR,
            CloudStorageErrorType::TIMEOUT => RecoveryStrategy::NETWORK_RETRY,
            
            CloudStorageErrorType::API_QUOTA_EXCEEDED => RecoveryStrategy::QUOTA_WAIT,
            
            CloudStorageErrorType::SERVICE_UNAVAILABLE => RecoveryStrategy::SERVICE_RETRY,
            
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            CloudStorageErrorType::FOLDER_ACCESS_DENIED => RecoveryStrategy::USER_INTERVENTION_REQUIRED,
            
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            CloudStorageErrorType::FILE_TOO_LARGE,
            CloudStorageErrorType::INVALID_FILE_TYPE => RecoveryStrategy::USER_INTERVENTION_REQUIRED,
            
            default => RecoveryStrategy::HEALTH_CHECK_RETRY,
        };
    }

    /**
     * Execute the determined recovery strategy.
     *
     * @param User $user The user whose connection to recover
     * @param string $provider The cloud storage provider name
     * @param RecoveryStrategy $strategy The recovery strategy to execute
     * @param CloudStorageErrorType|null $errorType The original error type
     * @param string $operationId Unique operation ID for logging
     * @return RecoveryResult
     */
    private function executeRecoveryStrategy(
        User $user,
        string $provider,
        RecoveryStrategy $strategy,
        ?CloudStorageErrorType $errorType,
        string $operationId
    ): RecoveryResult {
        Log::info('Executing recovery strategy', [
            'user_id' => $user->id,
            'provider' => $provider,
            'strategy' => $strategy->value,
            'error_type' => $errorType?->value,
            'operation_id' => $operationId
        ]);

        try {
            return match ($strategy) {
                RecoveryStrategy::TOKEN_REFRESH => $this->executeTokenRefreshRecovery($user, $provider, $operationId),
                RecoveryStrategy::NETWORK_RETRY => $this->executeNetworkRetryRecovery($user, $provider, $operationId),
                RecoveryStrategy::QUOTA_WAIT => $this->executeQuotaWaitRecovery($user, $provider, $operationId),
                RecoveryStrategy::SERVICE_RETRY => $this->executeServiceRetryRecovery($user, $provider, $operationId),
                RecoveryStrategy::HEALTH_CHECK_RETRY => $this->executeHealthCheckRetryRecovery($user, $provider, $operationId),
                RecoveryStrategy::USER_INTERVENTION_REQUIRED => $this->executeUserInterventionRequired($user, $provider, $errorType, $operationId),
                RecoveryStrategy::NO_ACTION_NEEDED => RecoveryResult::success(__('messages.recovery_no_action_needed'), $strategy),
                default => RecoveryResult::failure(__('messages.recovery_unknown_strategy'), $strategy),
            };

        } catch (Exception $e) {
            Log::error('Exception during recovery strategy execution', [
                'user_id' => $user->id,
                'provider' => $provider,
                'strategy' => $strategy->value,
                'operation_id' => $operationId,
                'error' => $e->getMessage()
            ]);

            return RecoveryResult::failure(
                __('messages.recovery_strategy_failed') . ': ' . $e->getMessage(),
                $strategy,
                $e
            );
        }
    }

    /**
     * Execute token refresh recovery strategy.
     */
    private function executeTokenRefreshRecovery(User $user, string $provider, string $operationId): RecoveryResult
    {
        try {
            $tokenCoordinator = app(TokenRefreshCoordinator::class);
            $refreshResult = $tokenCoordinator->coordinateRefresh($user, $provider);
            
            if ($refreshResult->isSuccessful()) {
                Log::info('Token refresh recovery successful', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'operation_id' => $operationId
                ]);
                
                return RecoveryResult::success(
                    __('messages.recovery_token_refreshed_successfully'),
                    RecoveryStrategy::TOKEN_REFRESH
                );
            } else {
                Log::warning('Token refresh recovery failed', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'operation_id' => $operationId,
                    'error' => $refreshResult->message
                ]);
                
                return RecoveryResult::failure(
                    __('messages.recovery_token_refresh_failed') . ': ' . $refreshResult->message,
                    RecoveryStrategy::TOKEN_REFRESH,
                    $refreshResult->exception
                );
            }
        } catch (Exception $e) {
            return RecoveryResult::failure(
                __('messages.recovery_token_refresh_exception') . ': ' . $e->getMessage(),
                RecoveryStrategy::TOKEN_REFRESH,
                $e
            );
        }
    }

    /**
     * Execute network retry recovery strategy.
     */
    private function executeNetworkRetryRecovery(User $user, string $provider, string $operationId): RecoveryResult
    {
        try {
            // Perform a simple connectivity test
            $healthStatus = $this->healthValidator->performLiveApiTest($user, $provider);
            
            if ($healthStatus->isSuccessful()) {
                Log::info('Network retry recovery successful', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'operation_id' => $operationId
                ]);
                
                return RecoveryResult::success(
                    __('messages.recovery_network_connectivity_restored'),
                    RecoveryStrategy::NETWORK_RETRY
                );
            } else {
                return RecoveryResult::failure(
                    __('messages.recovery_network_connectivity_still_failing'),
                    RecoveryStrategy::NETWORK_RETRY
                );
            }
        } catch (Exception $e) {
            return RecoveryResult::failure(
                __('messages.recovery_network_test_exception') . ': ' . $e->getMessage(),
                RecoveryStrategy::NETWORK_RETRY,
                $e
            );
        }
    }

    /**
     * Execute quota wait recovery strategy.
     */
    private function executeQuotaWaitRecovery(User $user, string $provider, string $operationId): RecoveryResult
    {
        // For quota issues, we just check if the quota has been restored
        try {
            $healthStatus = $this->healthValidator->performLiveApiTest($user, $provider);
            
            if ($healthStatus->isSuccessful()) {
                Log::info('Quota wait recovery successful', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'operation_id' => $operationId
                ]);
                
                return RecoveryResult::success(
                    __('messages.recovery_api_quota_restored'),
                    RecoveryStrategy::QUOTA_WAIT
                );
            } else {
                return RecoveryResult::failure(
                    __('messages.recovery_api_quota_still_exceeded'),
                    RecoveryStrategy::QUOTA_WAIT
                );
            }
        } catch (Exception $e) {
            return RecoveryResult::failure(
                __('messages.recovery_quota_check_exception') . ': ' . $e->getMessage(),
                RecoveryStrategy::QUOTA_WAIT,
                $e
            );
        }
    }

    /**
     * Execute service retry recovery strategy.
     */
    private function executeServiceRetryRecovery(User $user, string $provider, string $operationId): RecoveryResult
    {
        try {
            $healthStatus = $this->healthValidator->performLiveApiTest($user, $provider);
            
            if ($healthStatus->isSuccessful()) {
                Log::info('Service retry recovery successful', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'operation_id' => $operationId
                ]);
                
                return RecoveryResult::success(
                    __('messages.recovery_service_availability_restored'),
                    RecoveryStrategy::SERVICE_RETRY
                );
            } else {
                return RecoveryResult::failure(
                    __('messages.recovery_service_still_unavailable'),
                    RecoveryStrategy::SERVICE_RETRY
                );
            }
        } catch (Exception $e) {
            return RecoveryResult::failure(
                __('messages.recovery_service_check_exception') . ': ' . $e->getMessage(),
                RecoveryStrategy::SERVICE_RETRY,
                $e
            );
        }
    }

    /**
     * Execute health check retry recovery strategy.
     */
    private function executeHealthCheckRetryRecovery(User $user, string $provider, string $operationId): RecoveryResult
    {
        try {
            $healthStatus = $this->healthValidator->validateConnectionHealth($user, $provider);
            
            if ($healthStatus->isHealthy()) {
                Log::info('Health check retry recovery successful', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'operation_id' => $operationId
                ]);
                
                return RecoveryResult::success(
                    __('messages.recovery_connection_health_restored'),
                    RecoveryStrategy::HEALTH_CHECK_RETRY
                );
            } else {
                return RecoveryResult::failure(
                    __('messages.recovery_connection_still_unhealthy'),
                    RecoveryStrategy::HEALTH_CHECK_RETRY
                );
            }
        } catch (Exception $e) {
            return RecoveryResult::failure(
                __('messages.recovery_health_check_exception') . ': ' . $e->getMessage(),
                RecoveryStrategy::HEALTH_CHECK_RETRY,
                $e
            );
        }
    }

    /**
     * Execute user intervention required strategy.
     */
    private function executeUserInterventionRequired(
        User $user,
        string $provider,
        ?CloudStorageErrorType $errorType,
        string $operationId
    ): RecoveryResult {
        Log::info('User intervention required for recovery', [
            'user_id' => $user->id,
            'provider' => $provider,
            'error_type' => $errorType?->value,
            'operation_id' => $operationId
        ]);

        // Send notification to user about required intervention
        try {
            if ($errorType) {
                $tokenErrorType = $this->mapCloudStorageErrorToTokenError($errorType);
                $this->notificationService->sendRefreshFailureNotification(
                    $user,
                    $provider,
                    $tokenErrorType,
                    1, // attempt count
                    $errorType->getDescription()
                );
            }
        } catch (Exception $e) {
            Log::error('Failed to send user intervention notification', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId,
                'error' => $e->getMessage()
            ]);
        }

        return RecoveryResult::failure(
            __('messages.recovery_user_intervention_required') . ': ' . ($errorType?->getDescription() ?? __('messages.recovery_manual_action_needed')),
            RecoveryStrategy::USER_INTERVENTION_REQUIRED
        );
    }

    /**
     * Handle recovery failure by sending appropriate notifications.
     */
    private function handleRecoveryFailure(
        User $user,
        string $provider,
        ?CloudStorageErrorType $errorType,
        ?Exception $exception,
        string $operationId
    ): void {
        Log::warning('Handling recovery failure', [
            'user_id' => $user->id,
            'provider' => $provider,
            'error_type' => $errorType?->value,
            'operation_id' => $operationId,
            'exception' => $exception?->getMessage()
        ]);

        try {
            if ($errorType && $errorType->requiresUserIntervention()) {
                $tokenErrorType = $this->mapCloudStorageErrorToTokenError($errorType);
                $this->notificationService->sendRefreshFailureNotification(
                    $user,
                    $provider,
                    $tokenErrorType,
                    1, // attempt count
                    $exception?->getMessage()
                );
            }
        } catch (Exception $e) {
            Log::error('Failed to send recovery failure notification', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId,
                'notification_error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Map CloudStorageErrorType to TokenRefreshErrorType for notifications.
     */
    private function mapCloudStorageErrorToTokenError(CloudStorageErrorType $errorType): TokenRefreshErrorType
    {
        return match ($errorType) {
            CloudStorageErrorType::TOKEN_EXPIRED => TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN,
            CloudStorageErrorType::INVALID_CREDENTIALS => TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
            CloudStorageErrorType::NETWORK_ERROR => TokenRefreshErrorType::NETWORK_TIMEOUT,
            CloudStorageErrorType::API_QUOTA_EXCEEDED => TokenRefreshErrorType::API_QUOTA_EXCEEDED,
            CloudStorageErrorType::SERVICE_UNAVAILABLE => TokenRefreshErrorType::SERVICE_UNAVAILABLE,
            default => TokenRefreshErrorType::UNKNOWN_ERROR,
        };
    }
}