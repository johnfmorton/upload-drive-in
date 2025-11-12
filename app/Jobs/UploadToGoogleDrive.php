<?php

namespace App\Jobs;

use App\Contracts\CloudStorageProviderInterface;
use App\Contracts\CloudStorageErrorHandlerInterface;
use App\Enums\CloudStorageErrorType;
use App\Enums\TokenRefreshErrorType;
use App\Exceptions\CloudStorageException;
use App\Models\FileUpload;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageLogService;
use App\Services\CloudStorageManager;
use App\Services\TokenRefreshCoordinator;
use App\Services\TokenRenewalNotificationService;
use App\Services\RefreshResult;
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
     * Ensure the user has a valid token, refreshing if necessary.
     * This method coordinates token refresh to prevent duplicate operations
     * and implements automatic retry logic when token refresh succeeds.
     *
     * @param \App\Models\User $user The user whose token to validate
     * @param TokenRefreshCoordinator $coordinator The token refresh coordinator
     * @param TokenRenewalNotificationService $notificationService The notification service
     * @param string $operationId Unique operation ID for logging
     * @return bool True if token is valid or successfully refreshed, false otherwise
     */
    protected function ensureValidToken(
        \App\Models\User $user,
        TokenRefreshCoordinator $coordinator,
        TokenRenewalNotificationService $notificationService,
        string $operationId
    ): bool {
        Log::info('Ensuring valid token for upload job', [
            'user_id' => $user->id,
            'file_upload_id' => $this->fileUploadId,
            'operation_id' => $operationId,
            'attempt' => $this->attempts()
        ]);

        try {
            // Use TokenRefreshCoordinator for thread-safe token validation and refresh
            $refreshResult = $coordinator->coordinateRefresh($user, 'google-drive');
            
            if ($refreshResult->isSuccessful()) {
                Log::info('Token validation successful for upload job', [
                    'user_id' => $user->id,
                    'file_upload_id' => $this->fileUploadId,
                    'operation_id' => $operationId,
                    'result_type' => $refreshResult->wasAlreadyValid ? 'already_valid' : 
                                   ($refreshResult->wasRefreshedByAnotherProcess ? 'refreshed_by_another' : 'refreshed'),
                    'message' => $refreshResult->message
                ]);
                return true;
            } else {
                Log::error('Token validation failed for upload job', [
                    'user_id' => $user->id,
                    'file_upload_id' => $this->fileUploadId,
                    'operation_id' => $operationId,
                    'error' => $refreshResult->message,
                    'error_type' => $refreshResult->errorType?->value,
                    'requires_user_intervention' => $refreshResult->errorType?->requiresUserIntervention()
                ]);

                // Trigger appropriate notifications based on error type
                if ($refreshResult->errorType) {
                    $this->handleTokenRefreshFailure(
                        $user,
                        $refreshResult->errorType,
                        $refreshResult->exception,
                        $notificationService,
                        $operationId
                    );
                }

                return false;
            }
        } catch (Exception $e) {
            Log::error('Exception during token validation for upload job', [
                'user_id' => $user->id,
                'file_upload_id' => $this->fileUploadId,
                'operation_id' => $operationId,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e)
            ]);
            return false;
        }
    }

    /**
     * Handle token refresh failure by triggering appropriate notifications
     * and updating job retry behavior based on error type.
     *
     * @param \App\Models\User $user The user whose token refresh failed
     * @param TokenRefreshErrorType $errorType The type of refresh error
     * @param Exception|null $exception The original exception
     * @param TokenRenewalNotificationService $notificationService The notification service
     * @param string $operationId Unique operation ID for logging
     */
    protected function handleTokenRefreshFailure(
        \App\Models\User $user,
        TokenRefreshErrorType $errorType,
        ?Exception $exception,
        TokenRenewalNotificationService $notificationService,
        string $operationId
    ): void {
        Log::info('Handling token refresh failure for upload job', [
            'user_id' => $user->id,
            'file_upload_id' => $this->fileUploadId,
            'operation_id' => $operationId,
            'error_type' => $errorType->value,
            'requires_user_intervention' => $errorType->requiresUserIntervention(),
            'is_recoverable' => $errorType->isRecoverable()
        ]);

        // Update job retry behavior based on error type
        if ($errorType->isRecoverable()) {
            $maxAttempts = $errorType->getMaxRetryAttempts();
            if ($maxAttempts > 0) {
                $this->tries = min($maxAttempts + 1, $this->tries); // +1 because attempts start at 1
            }

            // Set retry delay for recoverable errors
            $retryDelay = $errorType->getRetryDelay($this->attempts());
            if ($retryDelay > 0) {
                $this->backoff = [$retryDelay];
            }

            Log::info('Token refresh error is recoverable, updating job retry settings', [
                'user_id' => $user->id,
                'file_upload_id' => $this->fileUploadId,
                'operation_id' => $operationId,
                'error_type' => $errorType->value,
                'max_attempts' => $this->tries,
                'retry_delay' => $retryDelay
            ]);
        } else {
            Log::warning('Token refresh error is not recoverable, job will fail', [
                'user_id' => $user->id,
                'file_upload_id' => $this->fileUploadId,
                'operation_id' => $operationId,
                'error_type' => $errorType->value,
                'requires_user_intervention' => $errorType->requiresUserIntervention()
            ]);
        }

        // Send appropriate notifications
        try {
            $notificationService->handleTokenRefreshFailure(
                $user,
                'google-drive',
                $errorType,
                $exception,
                $this->attempts()
            );

            Log::info('Token refresh failure notification sent', [
                'user_id' => $user->id,
                'file_upload_id' => $this->fileUploadId,
                'operation_id' => $operationId,
                'error_type' => $errorType->value
            ]);
        } catch (Exception $notificationException) {
            Log::error('Failed to send token refresh failure notification', [
                'user_id' => $user->id,
                'file_upload_id' => $this->fileUploadId,
                'operation_id' => $operationId,
                'error_type' => $errorType->value,
                'notification_error' => $notificationException->getMessage()
            ]);
        }
    }

    /**
     * Determine which user's cloud storage to use for the upload.
     *
     * @param FileUpload $fileUpload
     * @param CloudStorageManager $storageManager
     * @return \App\Models\User|null
     */
    protected function determineTargetUser(FileUpload $fileUpload, CloudStorageManager $storageManager): ?\App\Models\User
    {
        $targetUser = null;
        
        // Priority 1: If client selected a specific company user (employee), use their cloud storage
        if ($fileUpload->company_user_id) {
            $targetUser = \App\Models\User::find($fileUpload->company_user_id);
            if ($targetUser) {
                $hasValidConnection = $this->checkUserConnection($targetUser, $storageManager);
                Log::info('Using selected company user for upload.', [
                    'company_user_id' => $fileUpload->company_user_id,
                    'target_user_id' => $targetUser->id,
                    'target_user_email' => $targetUser->email,
                    'has_valid_connection' => $hasValidConnection,
                ]);
            }
        }
        
        // Priority 2: If this upload was made by an employee directly, use their cloud storage
        if (!$targetUser && $fileUpload->uploaded_by_user_id) {
            $targetUser = \App\Models\User::find($fileUpload->uploaded_by_user_id);
            if ($targetUser) {
                $hasValidConnection = $this->checkUserConnection($targetUser, $storageManager);
                Log::info('Using employee uploader for upload.', [
                    'uploaded_by_user_id' => $fileUpload->uploaded_by_user_id,
                    'target_user_id' => $targetUser->id,
                    'target_user_email' => $targetUser->email,
                    'has_valid_connection' => $hasValidConnection,
                ]);
            }
        }
        
        // Priority 3: Only fall back to admin if target user doesn't have valid connection OR no target user found
        if (!$targetUser) {
            // No target user found at all, try admin
            $adminUser = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
            if ($adminUser && $this->checkUserConnection($adminUser, $storageManager)) {
                $targetUser = $adminUser;
                Log::info('No target user found, using admin user as fallback.', [
                    'admin_id' => $targetUser->id,
                ]);
            }
        } elseif (!$this->checkUserConnection($targetUser, $storageManager)) {
            // Target user exists but doesn't have valid connection, try admin fallback
            Log::warning('Selected user does not have valid cloud storage connection, attempting admin fallback.', [
                'selected_user_id' => $targetUser->id,
                'selected_user_email' => $targetUser->email,
            ]);
            
            $adminUser = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
            if ($adminUser && $this->checkUserConnection($adminUser, $storageManager)) {
                $targetUser = $adminUser;
                Log::info('Using admin user as fallback for upload.', [
                    'admin_id' => $targetUser->id,
                    'original_company_user_id' => $fileUpload->company_user_id,
                    'original_uploaded_by_user_id' => $fileUpload->uploaded_by_user_id,
                ]);
            } else {
                Log::error('Selected user has no valid connection and admin also has no valid connection.', [
                    'selected_user_id' => $targetUser->id ?? null,
                    'admin_has_connection' => $adminUser ? $this->checkUserConnection($adminUser, $storageManager) : false,
                ]);
                return null;
            }
        } else {
            // Target user exists and has valid connection - use them!
            Log::info('Using target user with valid cloud storage connection.', [
                'target_user_id' => $targetUser->id,
                'target_user_email' => $targetUser->email,
            ]);
        }

        return $targetUser;
    }

    /**
     * Check if a user has a valid connection to any available provider.
     * For OAuth providers (Google Drive), checks for valid tokens.
     * For API key providers (S3), checks for system-level configuration.
     *
     * @param \App\Models\User $user
     * @param CloudStorageManager $storageManager
     * @return bool
     */
    protected function checkUserConnection(\App\Models\User $user, CloudStorageManager $storageManager): bool
    {
        try {
            $provider = $storageManager->getUserProvider($user);
            $hasConnection = $provider->hasValidConnection($user);
            
            Log::debug('Checked user connection', [
                'user_id' => $user->id,
                'provider' => $provider->getProviderName(),
                'authentication_type' => $provider->getAuthenticationType(),
                'has_connection' => $hasConnection,
            ]);
            
            return $hasConnection;
        } catch (Exception $e) {
            Log::warning('Failed to check user connection', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
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
        $providerName = $context['provider'] ?? $fileUpload->cloud_storage_provider ?? 'unknown';
        $fileUpload->update([
            'cloud_storage_provider' => $providerName,
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
                    $providerName, 
                    $exception->getMessage(),
                    $errorType
                );
            }
        }

        // Log retry decision if this isn't the final failure
        if (isset($context['target_user_id']) && !($context['final_failure'] ?? false)) {
            $targetUser = \App\Models\User::find($context['target_user_id']);
            if ($targetUser && $errorType) {
                try {
                    $storageManager = app(CloudStorageManager::class);
                    $provider = $storageManager->getUserProvider($targetUser);
                    $errorHandler = $storageManager->getErrorHandler($provider->getProviderName());
                    
                    $shouldRetry = $errorHandler->shouldRetry($errorType, $this->attempts());
                    $retryDelay = $shouldRetry ? $errorHandler->getRetryDelay($errorType, $this->attempts()) : null;
                    
                    $logService->logRetryDecision(
                        $context['operation_id'] ?? 'job_' . $this->fileUploadId,
                        'upload_job',
                        $provider->getProviderName(),
                        $targetUser,
                        $errorType,
                        $this->attempts(),
                        $shouldRetry,
                        $retryDelay,
                        [
                            'file_upload_id' => $fileUpload->id,
                            'filename' => $fileUpload->original_filename,
                            'client_email' => $fileUpload->email,
                            'token_refresh_retry' => $context['token_refresh_retry'] ?? false,
                        ]
                    );
                } catch (Exception $logError) {
                    Log::warning('Failed to log retry decision', [
                        'file_upload_id' => $fileUpload->id,
                        'error' => $logError->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Handle job failure with enhanced token refresh error handling.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        $operationId = uniqid('failed_', true);
        $fileUpload = FileUpload::find($this->fileUploadId);
        
        if (!$fileUpload) {
            Log::warning('FileUpload record no longer exists during failure handling', [
                'file_upload_id' => $this->fileUploadId,
                'operation_id' => $operationId,
                'error' => $exception->getMessage(),
            ]);
            return;
        }

        Log::error('Upload job permanently failed, processing final failure', [
            'file_upload_id' => $fileUpload->id,
            'operation_id' => $operationId,
            'total_attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'exception_type' => get_class($exception),
        ]);

        // Get services for final failure processing
        $storageManager = app(CloudStorageManager::class);
        $healthService = app(CloudStorageHealthService::class);
        $notificationService = app(TokenRenewalNotificationService::class);
        
        // Try to get appropriate error handler and target user
        $errorHandler = null;
        $targetUser = null;
        $provider = null;
        
        try {
            if ($fileUpload->company_user_id || $fileUpload->uploaded_by_user_id) {
                $targetUser = \App\Models\User::find($fileUpload->company_user_id ?? $fileUpload->uploaded_by_user_id);
                if ($targetUser) {
                    $provider = $storageManager->getUserProvider($targetUser);
                    $errorHandler = $storageManager->getErrorHandler($provider->getProviderName());
                }
            }
            
            if (!$errorHandler) {
                $defaultProvider = $storageManager->getDefaultProvider();
                $errorHandler = $storageManager->getErrorHandler($defaultProvider->getProviderName());
                $provider = $defaultProvider;
            }
        } catch (Exception $e) {
            Log::warning('Failed to get error handler for failure processing', [
                'file_upload_id' => $fileUpload->id,
                'operation_id' => $operationId,
                'error' => $e->getMessage()
            ]);
        }
        
        $errorType = null;
        if ($exception instanceof CloudStorageException) {
            $errorType = $exception->getErrorType();
        } elseif ($errorHandler) {
            $errorType = $errorHandler->classifyError($exception);
        } else {
            $errorType = CloudStorageErrorType::UNKNOWN_ERROR;
        }

        // Check if this is a token-related failure that requires notification
        // Only send token notifications for OAuth providers (not API key providers like S3)
        $isTokenError = $this->isTokenRelatedError($errorType, $exception);
        $isOAuthProvider = $provider && $provider->getAuthenticationType() === 'oauth';
        
        if ($isTokenError && $isOAuthProvider && $targetUser) {
            Log::info('Final failure is token-related for OAuth provider, triggering token renewal notifications', [
                'file_upload_id' => $fileUpload->id,
                'operation_id' => $operationId,
                'provider' => $provider->getProviderName(),
                'target_user_id' => $targetUser->id,
                'error_type' => $errorType->value
            ]);

            try {
                // Map CloudStorageErrorType to TokenRefreshErrorType for notifications
                $tokenErrorType = $this->mapToTokenRefreshErrorType($errorType);
                
                $notificationService->handleTokenRefreshFailure(
                    $targetUser,
                    $provider->getProviderName(),
                    $tokenErrorType,
                    $exception,
                    $this->attempts()
                );
            } catch (Exception $notificationError) {
                Log::error('Failed to send token failure notification', [
                    'file_upload_id' => $fileUpload->id,
                    'operation_id' => $operationId,
                    'provider' => $provider->getProviderName(),
                    'target_user_id' => $targetUser->id,
                    'notification_error' => $notificationError->getMessage()
                ]);
            }
        } else if ($isTokenError && !$isOAuthProvider) {
            Log::info('Token-related error for API key provider, skipping token notifications', [
                'file_upload_id' => $fileUpload->id,
                'operation_id' => $operationId,
                'provider' => $provider?->getProviderName() ?? 'unknown',
                'authentication_type' => $provider?->getAuthenticationType() ?? 'unknown',
                'error_type' => $errorType->value,
            ]);
        }

        // Get log service for final failure processing
        $logService = app(CloudStorageLogService::class);
        
        // Record the final failure with enhanced context
        $this->recordError($fileUpload, $exception, $errorType, $healthService, $logService, [
            'operation_id' => $operationId,
            'final_failure' => true,
            'total_attempts' => $this->attempts(),
            'target_user_id' => $targetUser?->id,
            'is_token_error' => $isTokenError,
            'provider' => $provider?->getProviderName() ?? 'unknown',
        ]);

        Log::error('Cloud storage upload job permanently failed after all retries with token refresh support', [
            'file_upload_id' => $fileUpload->id,
            'operation_id' => $operationId,
            'provider' => $provider?->getProviderName() ?? $fileUpload->cloud_storage_provider ?? 'unknown',
            'total_attempts' => $this->attempts(),
            'error_type' => $errorType?->value,
            'error_severity' => $errorType?->getSeverity(),
            'requires_user_intervention' => $errorType?->requiresUserIntervention(),
            'is_token_error' => $isTokenError,
            'error' => $exception->getMessage(),
            'exception_type' => get_class($exception),
        ]);
    }

    /**
     * Map CloudStorageErrorType to TokenRefreshErrorType for notification purposes.
     *
     * @param CloudStorageErrorType $errorType The cloud storage error type
     * @return TokenRefreshErrorType The corresponding token refresh error type
     */
    protected function mapToTokenRefreshErrorType(CloudStorageErrorType $errorType): TokenRefreshErrorType
    {
        return match ($errorType) {
            CloudStorageErrorType::INVALID_CREDENTIALS => TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
            CloudStorageErrorType::TOKEN_EXPIRED => TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
            CloudStorageErrorType::NETWORK_ERROR => TokenRefreshErrorType::NETWORK_TIMEOUT,
            CloudStorageErrorType::API_QUOTA_EXCEEDED => TokenRefreshErrorType::API_QUOTA_EXCEEDED,
            CloudStorageErrorType::SERVICE_UNAVAILABLE => TokenRefreshErrorType::SERVICE_UNAVAILABLE,
            default => TokenRefreshErrorType::UNKNOWN_ERROR,
        };
    }

    /**
     * Execute the job.
     * Uploads the file associated with the FileUpload record to cloud storage using CloudStorageManager.
     * Includes automatic token refresh and retry coordination.
     *
     * @param CloudStorageManager $storageManager Injected cloud storage manager
     * @param CloudStorageHealthService $healthService Injected health monitoring service
     * @param CloudStorageLogService $logService Injected logging service
     * @param TokenRefreshCoordinator $tokenCoordinator Injected token refresh coordinator
     * @param TokenRenewalNotificationService $notificationService Injected notification service
     * @return void
     * @throws Exception Throws exceptions on failure, allowing the queue worker to handle retries/failures.
     */
    public function handle(
        CloudStorageManager $storageManager,
        CloudStorageHealthService $healthService,
        CloudStorageLogService $logService,
        TokenRefreshCoordinator $tokenCoordinator,
        TokenRenewalNotificationService $notificationService
    ): void
    {
        $operationId = uniqid('upload_', true);
        
        // Try to find the FileUpload record
        $fileUpload = FileUpload::find($this->fileUploadId);
        
        if (!$fileUpload) {
            Log::warning('FileUpload record no longer exists, skipping job', [
                'file_upload_id' => $this->fileUploadId,
                'operation_id' => $operationId
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

        Log::info('Starting cloud storage upload job with token refresh support', [
            'file_upload_id' => $fileUpload->id,
            'operation_id' => $operationId,
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

            // 2. Determine which user's cloud storage to use and get appropriate provider
            $targetUser = $this->determineTargetUser($fileUpload, $storageManager);
            
            if (!$targetUser) {
                $error = new Exception('No target user found for cloud storage upload');
                $errorType = CloudStorageErrorType::INVALID_CREDENTIALS;
                $this->recordError($fileUpload, $error, $errorType, $healthService, $logService, [
                    'operation_id' => $operationId,
                    'error_category' => 'configuration',
                    'permanent_failure' => true,
                ]);
                throw $error;
            }

            // Get the appropriate provider for the target user
            $provider = $storageManager->getUserProvider($targetUser);
            $errorHandler = $storageManager->getErrorHandler($provider->getProviderName());

            // 3. Ensure the target user has a valid token before proceeding (OAuth providers only)
            // S3 and other API key providers use system-level credentials and don't need token validation
            $authType = $provider->getAuthenticationType();
            
            if ($authType === 'oauth') {
                Log::info('OAuth provider detected, ensuring valid token before upload', [
                    'file_upload_id' => $fileUpload->id,
                    'operation_id' => $operationId,
                    'provider' => $provider->getProviderName(),
                    'target_user_id' => $targetUser->id,
                    'attempt' => $this->attempts()
                ]);

                $tokenValid = $this->ensureValidToken($targetUser, $tokenCoordinator, $notificationService, $operationId);
                
                if (!$tokenValid) {
                    $error = new Exception('Token validation failed for target user');
                    $errorType = CloudStorageErrorType::INVALID_CREDENTIALS;
                    $this->recordError($fileUpload, $error, $errorType, $healthService, $logService, [
                        'operation_id' => $operationId,
                        'target_user_id' => $targetUser->id,
                        'provider' => $provider->getProviderName(),
                        'error_category' => 'authentication',
                        'token_validation_failed' => true,
                    ]);
                    throw $error;
                }

                Log::info('Token validation successful, proceeding with upload', [
                    'file_upload_id' => $fileUpload->id,
                    'operation_id' => $operationId,
                    'provider' => $provider->getProviderName(),
                    'target_user_id' => $targetUser->id,
                    'attempt' => $this->attempts()
                ]);
            } else {
                Log::info('API key provider detected, skipping token validation', [
                    'file_upload_id' => $fileUpload->id,
                    'operation_id' => $operationId,
                    'provider' => $provider->getProviderName(),
                    'authentication_type' => $authType,
                    'target_user_id' => $targetUser->id,
                ]);
            }

            Log::info('Provider selected for upload', [
                'file_upload_id' => $fileUpload->id,
                'operation_id' => $operationId,
                'provider' => $provider->getProviderName(),
                'authentication_type' => $provider->getAuthenticationType(),
                'storage_model' => $provider->getStorageModel(),
                'target_user_id' => $targetUser->id,
            ]);

            // 4. Upload the file using the provider interface
            $description = "Uploaded by: " . $email . "\nMessage: " . ($message ?? 'No message');
            $metadata = [
                'original_filename' => $originalFilename,
                'mime_type' => $mimeType,
                'description' => $description,
                'client_email' => $email,
                'message' => $message,
            ];

            Log::info('Attempting file upload via cloud storage provider with validated token', [
                 'file_upload_id' => $fileUpload->id,
                 'operation_id' => $operationId,
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

            // 5. Update the FileUpload record with the cloud storage ID and clear error info
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
                'operation_id' => $operationId,
                'file_id' => $cloudFileId,
                'file_name' => $originalFilename,
                'client_email' => $email,
            ]);

            Log::info('Cloud storage upload job completed successfully with token refresh support', [
                'file_upload_id' => $fileUpload->id,
                'operation_id' => $operationId,
                'provider' => $provider->getProviderName(),
                'cloud_file_id' => $cloudFileId,
                'target_user_id' => $targetUser->id,
                'total_attempts' => $this->attempts(),
            ]);

            // 6. Delete the local file after successful upload
            try {
                Storage::disk('public')->delete($localPath);
                Log::info('Deleted local file after successful cloud storage upload', [
                    'file_upload_id' => $fileUpload->id,
                    'operation_id' => $operationId,
                    'path' => $localPath,
                    'provider' => $provider->getProviderName()
                ]);
            } catch (Exception $e) {
                Log::warning('Failed to delete local file after cloud storage upload', [
                    'file_upload_id' => $fileUpload->id,
                    'operation_id' => $operationId,
                    'path' => $localPath, 
                    'provider' => $provider->getProviderName(),
                    'error' => $e->getMessage()
                ]);
            }

        } catch (Exception $e) {
            $this->handleUploadException(
                $e, 
                $fileUpload, 
                $storageManager, 
                $healthService, 
                $logService, 
                $tokenCoordinator, 
                $notificationService, 
                $operationId, 
                $localPath, 
                $email, 
                $originalFilename, 
                $mimeType, 
                $targetUser ?? null
            );
        }
    }

    /**
     * Handle upload exceptions with automatic retry logic for token refresh scenarios.
     * This method implements sophisticated retry coordination and error classification.
     *
     * @param Exception $e The exception that occurred
     * @param FileUpload $fileUpload The file upload record
     * @param CloudStorageManager $storageManager The storage manager
     * @param CloudStorageHealthService $healthService The health service
     * @param CloudStorageLogService $logService The log service
     * @param TokenRefreshCoordinator $tokenCoordinator The token coordinator
     * @param TokenRenewalNotificationService $notificationService The notification service
     * @param string $operationId The operation ID
     * @param string $localPath The local file path
     * @param string $email The client email
     * @param string $originalFilename The original filename
     * @param string $mimeType The file MIME type
     * @param \App\Models\User|null $targetUser The target user
     * @throws Exception Re-throws the exception for queue retry handling
     */
    protected function handleUploadException(
        Exception $e,
        FileUpload $fileUpload,
        CloudStorageManager $storageManager,
        CloudStorageHealthService $healthService,
        CloudStorageLogService $logService,
        TokenRefreshCoordinator $tokenCoordinator,
        TokenRenewalNotificationService $notificationService,
        string $operationId,
        string $localPath,
        string $email,
        string $originalFilename,
        string $mimeType,
        ?\App\Models\User $targetUser
    ): void {
        Log::error('Upload exception occurred, analyzing for token refresh retry', [
            'file_upload_id' => $fileUpload->id,
            'operation_id' => $operationId,
            'error' => $e->getMessage(),
            'error_class' => get_class($e),
            'attempt' => $this->attempts(),
            'target_user_id' => $targetUser?->id
        ]);

        // Get provider and error handler for error processing
        $provider = null;
        $errorHandler = null;
        
        try {
            if ($targetUser) {
                $provider = $storageManager->getUserProvider($targetUser);
                $errorHandler = $storageManager->getErrorHandler($provider->getProviderName());
            } else {
                // Fallback to default provider for error classification
                $provider = $storageManager->getDefaultProvider();
                $errorHandler = $storageManager->getErrorHandler($provider->getProviderName());
            }
        } catch (Exception $providerError) {
            Log::warning('Failed to get provider for error handling, using generic error classification', [
                'file_upload_id' => $fileUpload->id,
                'operation_id' => $operationId,
                'error' => $providerError->getMessage()
            ]);
        }

        // Classify the error using the error handler
        $errorType = null;
        if ($e instanceof CloudStorageException) {
            $errorType = $e->getErrorType();
        } elseif ($errorHandler) {
            $errorType = $errorHandler->classifyError($e);
        } else {
            $errorType = CloudStorageErrorType::UNKNOWN_ERROR;
        }

        // Check if this is a token-related error that might be resolved by refresh
        // Only attempt token refresh for OAuth providers (not API key providers like S3)
        $isTokenError = $this->isTokenRelatedError($errorType, $e);
        $isOAuthProvider = $provider && $provider->getAuthenticationType() === 'oauth';
        
        if ($isTokenError && $isOAuthProvider && $targetUser && $this->attempts() <= 2) {
            Log::info('Detected token-related error for OAuth provider, attempting coordinated refresh for retry', [
                'file_upload_id' => $fileUpload->id,
                'operation_id' => $operationId,
                'provider' => $provider->getProviderName(),
                'error_type' => $errorType->value,
                'attempt' => $this->attempts(),
                'target_user_id' => $targetUser->id
            ]);

            // Attempt token refresh for potential retry (OAuth providers only)
            $refreshResult = $tokenCoordinator->coordinateRefresh($targetUser, $provider->getProviderName());
            
            if ($refreshResult->isSuccessful()) {
                Log::info('Token refresh successful after upload failure, job will retry', [
                    'file_upload_id' => $fileUpload->id,
                    'operation_id' => $operationId,
                    'provider' => $provider->getProviderName(),
                    'target_user_id' => $targetUser->id,
                    'refresh_result' => $refreshResult->wasTokenRefreshed() ? 'refreshed' : 'already_valid'
                ]);

                // Update retry settings for token refresh retry
                $this->tries = max($this->tries, $this->attempts() + 2); // Allow at least 2 more attempts
                $this->backoff = [30]; // Short delay for token refresh retry
                
                // Record the error but don't fail immediately - let it retry
                $context = [
                    'operation_id' => $operationId,
                    'local_path' => $localPath,
                    'email' => $email,
                    'target_user_id' => $targetUser->id,
                    'original_filename' => $originalFilename,
                    'mime_type' => $mimeType,
                    'provider' => $provider->getProviderName(),
                    'authentication_type' => $provider->getAuthenticationType(),
                    'token_refresh_retry' => true,
                    'refresh_successful' => true,
                ];
                
                $this->recordError($fileUpload, $e, $errorType, $healthService, $logService, $context);
                
                // Re-throw for retry
                throw $e;
            } else {
                Log::warning('Token refresh failed after upload failure, normal error handling will proceed', [
                    'file_upload_id' => $fileUpload->id,
                    'operation_id' => $operationId,
                    'provider' => $provider->getProviderName(),
                    'target_user_id' => $targetUser->id,
                    'refresh_error' => $refreshResult->message,
                    'refresh_error_type' => $refreshResult->errorType?->value
                ]);
            }
        } else if ($isTokenError && !$isOAuthProvider) {
            Log::info('Token-related error detected for API key provider, skipping token refresh', [
                'file_upload_id' => $fileUpload->id,
                'operation_id' => $operationId,
                'provider' => $provider?->getProviderName() ?? 'unknown',
                'authentication_type' => $provider?->getAuthenticationType() ?? 'unknown',
                'error_type' => $errorType->value,
            ]);
        }

        // Record detailed error information with enhanced context
        $context = [
            'operation_id' => $operationId,
            'local_path' => $localPath,
            'email' => $email,
            'target_user_id' => $targetUser?->id,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'provider' => $provider?->getProviderName() ?? 'unknown',
            'is_token_error' => $isTokenError,
        ];
        
        $this->recordError($fileUpload, $e, $errorType, $healthService, $logService, $context);

        // Determine retry logic based on error handler
        if ($errorHandler) {
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

            Log::error('Cloud storage upload job failed with enhanced error handling and token refresh support', [
                'file_upload_id' => $fileUpload->id,
                'operation_id' => $operationId,
                'provider' => $provider?->getProviderName() ?? 'unknown',
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
                'is_token_error' => $isTokenError,
            ]);

            // For errors that don't allow retries, fail immediately
            if (!$shouldRetry) {
                Log::warning('Error type does not allow retries, failing job immediately', [
                    'file_upload_id' => $fileUpload->id,
                    'operation_id' => $operationId,
                    'error_type' => $errorType->value,
                    'requires_user_intervention' => $errorType->requiresUserIntervention(),
                    'error' => $e->getMessage(),
                ]);
                $this->fail($e);
                return;
            }
        } else {
            Log::error('Cloud storage upload job failed without error handler', [
                'file_upload_id' => $fileUpload->id,
                'operation_id' => $operationId,
                'email' => $email,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'attempt' => $this->attempts(),
            ]);
        }

        // Re-throw the exception to let the queue worker handle retries
        throw $e;
    }

    /**
     * Check if an error is token-related and might be resolved by token refresh.
     *
     * @param CloudStorageErrorType $errorType The classified error type
     * @param Exception $exception The original exception
     * @return bool True if this is a token-related error
     */
    protected function isTokenRelatedError(CloudStorageErrorType $errorType, Exception $exception): bool
    {
        // Check error type classification
        if (in_array($errorType, [
            CloudStorageErrorType::INVALID_CREDENTIALS,
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS
        ])) {
            return true;
        }

        // Check exception message for token-related keywords
        $message = strtolower($exception->getMessage());
        $tokenKeywords = [
            'token',
            'expired',
            'invalid_grant',
            'unauthorized',
            'authentication',
            'credentials',
            'access denied'
        ];

        foreach ($tokenKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }


}
