<?php

namespace App\Services;

use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use App\Models\FileUpload;
use App\Jobs\UploadToGoogleDrive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Service for handling enhanced cloud storage reconnection flows
 * 
 * Provides unified reconnection handling across all cloud storage providers
 * with automatic retry of pending uploads and connection validation
 */
class CloudStorageReconnectionService
{
    public function __construct(
        private readonly CloudStorageHealthService $healthService
    ) {}

    /**
     * Initiate reconnection flow for a user and provider
     *
     * @param User $user The user to reconnect
     * @param string $provider The provider name (e.g., 'google-drive')
     * @param CloudStorageProviderInterface $providerInstance The provider instance
     * @return string The OAuth authorization URL
     * @throws \Exception If reconnection cannot be initiated
     */
    public function initiateReconnection(
        User $user, 
        string $provider, 
        CloudStorageProviderInterface $providerInstance
    ): string {
        Log::info('Initiating cloud storage reconnection', [
            'user_id' => $user->id,
            'provider' => $provider
        ]);

        try {
            // Mark the connection as requiring attention
            $this->healthService->markConnectionAsUnhealthy(
                $user,
                $provider,
                'User initiated reconnection',
                \App\Enums\CloudStorageErrorType::TOKEN_EXPIRED
            );

            // Generate auth URL with reconnection flag
            $authUrl = $providerInstance->getAuthUrl($user, true);

            Log::info('Reconnection URL generated successfully', [
                'user_id' => $user->id,
                'provider' => $provider
            ]);

            return $authUrl;

        } catch (\Exception $e) {
            Log::error('Failed to initiate reconnection', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Validate connection after OAuth completion
     *
     * @param User $user The user whose connection to validate
     * @param string $provider The provider name
     * @param CloudStorageProviderInterface $providerInstance The provider instance
     * @return array Validation result with success status and details
     */
    public function validateConnection(
        User $user, 
        string $provider, 
        CloudStorageProviderInterface $providerInstance
    ): array {
        Log::info('Validating connection after OAuth completion', [
            'user_id' => $user->id,
            'provider' => $provider
        ]);

        try {
            // Get connection health status
            $healthStatus = $providerInstance->getConnectionHealth($user);
            
            $isValid = $healthStatus->isHealthy() || $healthStatus->isDegraded();
            
            if ($isValid) {
                // Record successful connection
                $this->healthService->recordSuccessfulOperation($user, $provider, [
                    'operation' => 'reconnection_validation',
                    'validated_at' => now()->toISOString(),
                    'health_status' => $healthStatus->status
                ]);
            }

            $result = [
                'success' => $isValid,
                'health_status' => $healthStatus->status,
                'requires_reconnection' => $healthStatus->requiresReconnection,
                'last_error' => $healthStatus->lastErrorMessage,
                'provider_data' => $healthStatus->providerSpecificData
            ];

            Log::info('Connection validation completed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'is_valid' => $isValid,
                'health_status' => $healthStatus->status
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Connection validation failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'health_status' => 'unhealthy'
            ];
        }
    }

    /**
     * Retry pending uploads for a user after successful reconnection
     *
     * @param User $user The user whose uploads to retry
     * @param string $provider The provider name
     * @param int $limit Maximum number of uploads to retry
     * @return array Retry result with counts and details
     */
    public function retryPendingUploads(User $user, string $provider, int $limit = 50): array
    {
        Log::info('Starting automatic retry of pending uploads after reconnection', [
            'user_id' => $user->id,
            'provider' => $provider,
            'limit' => $limit
        ]);

        try {
            // Find pending uploads for this user that failed due to connection issues
            $pendingUploads = $this->findRetryableUploads($user, $provider, $limit);

            if ($pendingUploads->isEmpty()) {
                Log::info('No retryable uploads found after reconnection', [
                    'user_id' => $user->id,
                    'provider' => $provider
                ]);

                return [
                    'success' => true,
                    'total_found' => 0,
                    'successfully_queued' => 0,
                    'failed_to_queue' => 0,
                    'uploads' => []
                ];
            }

            $successfullyQueued = 0;
            $failedToQueue = 0;
            $uploadDetails = [];

            foreach ($pendingUploads as $upload) {
                try {
                    // Clear previous error information
                    $upload->update([
                        'cloud_storage_error_type' => null,
                        'cloud_storage_error_context' => null,
                        'connection_health_at_failure' => null,
                        'last_error' => null,
                        'error_details' => null,
                        'retry_recommended_at' => now()
                    ]);

                    // Dispatch the upload job based on provider
                    $this->dispatchUploadJob($upload, $provider);
                    $successfullyQueued++;

                    $uploadDetails[] = [
                        'id' => $upload->id,
                        'filename' => $upload->original_filename,
                        'status' => 'queued'
                    ];

                    Log::debug('Queued upload for retry after reconnection', [
                        'upload_id' => $upload->id,
                        'filename' => $upload->original_filename,
                        'user_id' => $user->id,
                        'provider' => $provider
                    ]);

                } catch (\Exception $e) {
                    $failedToQueue++;
                    
                    $uploadDetails[] = [
                        'id' => $upload->id,
                        'filename' => $upload->original_filename,
                        'status' => 'failed_to_queue',
                        'error' => $e->getMessage()
                    ];

                    Log::warning('Failed to queue upload for retry after reconnection', [
                        'upload_id' => $upload->id,
                        'user_id' => $user->id,
                        'provider' => $provider,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $result = [
                'success' => true,
                'total_found' => $pendingUploads->count(),
                'successfully_queued' => $successfullyQueued,
                'failed_to_queue' => $failedToQueue,
                'uploads' => $uploadDetails
            ];

            Log::info('Completed automatic retry of pending uploads after reconnection', [
                'user_id' => $user->id,
                'provider' => $provider,
                'result' => $result
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to retry pending uploads after reconnection', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'total_found' => 0,
                'successfully_queued' => 0,
                'failed_to_queue' => 0,
                'uploads' => []
            ];
        }
    }

    /**
     * Find uploads that can be retried after reconnection
     *
     * @param User $user The user whose uploads to find
     * @param string $provider The provider name
     * @param int $limit Maximum number of uploads to find
     * @return Collection Collection of FileUpload models
     */
    private function findRetryableUploads(User $user, string $provider, int $limit): Collection
    {
        return FileUpload::where(function($query) use ($user) {
            $query->where('company_user_id', $user->id)
                  ->orWhere('uploaded_by_user_id', $user->id);
        })
        ->whereNull('google_drive_file_id') // Still pending
        ->where(function($query) {
            // Only retry uploads that failed due to connection issues
            $query->whereIn('cloud_storage_error_type', [
                'token_expired',
                'insufficient_permissions',
                'invalid_credentials'
            ])->orWhereNull('cloud_storage_error_type');
        })
        ->where(function($query) {
            // Don't retry uploads that have been retried too recently
            $query->whereNull('retry_recommended_at')
                  ->orWhere('retry_recommended_at', '<', now()->subMinutes(5));
        })
        ->orderBy('created_at', 'asc')
        ->limit($limit)
        ->get();
    }

    /**
     * Dispatch upload job based on provider
     *
     * @param FileUpload $upload The upload to dispatch
     * @param string $provider The provider name
     * @return void
     * @throws \Exception If provider is not supported
     */
    private function dispatchUploadJob(FileUpload $upload, string $provider): void
    {
        switch ($provider) {
            case 'google-drive':
                UploadToGoogleDrive::dispatch($upload);
                break;
                
            default:
                throw new \Exception("Upload job dispatch not implemented for provider: {$provider}");
        }
    }

    /**
     * Handle reconnection failure with appropriate fallback
     *
     * @param User $user The user whose reconnection failed
     * @param string $provider The provider name
     * @param \Exception $exception The failure exception
     * @param array $context Additional context
     * @return array Failure handling result
     */
    public function handleReconnectionFailure(
        User $user, 
        string $provider, 
        \Exception $exception, 
        array $context = []
    ): array {
        Log::error('Handling reconnection failure', [
            'user_id' => $user->id,
            'provider' => $provider,
            'error' => $exception->getMessage(),
            'context' => $context
        ]);

        try {
            // Classify the error for better handling
            $errorType = $this->classifyReconnectionError($exception);
            
            // Update health status with failure information
            $this->healthService->markConnectionAsUnhealthy(
                $user,
                $provider,
                $exception->getMessage(),
                $errorType
            );

            // Generate user-friendly error message
            $userMessage = $this->generateUserFriendlyErrorMessage($errorType, $exception, $context);
            
            // Determine if retry is possible
            $canRetry = $this->canRetryReconnection($errorType);
            
            // Generate recommended actions
            $recommendedActions = $this->getReconnectionRecommendedActions($errorType, $provider);

            $result = [
                'success' => false,
                'error_type' => $errorType->value,
                'user_message' => $userMessage,
                'can_retry' => $canRetry,
                'recommended_actions' => $recommendedActions,
                'requires_admin_intervention' => $errorType->requiresUserIntervention()
            ];

            Log::info('Reconnection failure handled', [
                'user_id' => $user->id,
                'provider' => $provider,
                'result' => $result
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to handle reconnection failure', [
                'user_id' => $user->id,
                'provider' => $provider,
                'original_error' => $exception->getMessage(),
                'handling_error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error_type' => 'unknown_error',
                'user_message' => 'An unexpected error occurred during reconnection. Please try again or contact support.',
                'can_retry' => true,
                'recommended_actions' => ['Try reconnecting again', 'Contact support if the problem persists'],
                'requires_admin_intervention' => false
            ];
        }
    }

    /**
     * Classify reconnection error for appropriate handling
     *
     * @param \Exception $exception The exception to classify
     * @return \App\Enums\CloudStorageErrorType The classified error type
     */
    private function classifyReconnectionError(\Exception $exception): \App\Enums\CloudStorageErrorType
    {
        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'invalid_grant') || str_contains($message, 'expired')) {
            return \App\Enums\CloudStorageErrorType::TOKEN_EXPIRED;
        }

        if (str_contains($message, 'access_denied')) {
            return \App\Enums\CloudStorageErrorType::INSUFFICIENT_PERMISSIONS;
        }

        if (str_contains($message, 'invalid_client')) {
            return \App\Enums\CloudStorageErrorType::INVALID_CREDENTIALS;
        }

        if (str_contains($message, 'network') || str_contains($message, 'connection')) {
            return \App\Enums\CloudStorageErrorType::NETWORK_ERROR;
        }

        return \App\Enums\CloudStorageErrorType::UNKNOWN_ERROR;
    }

    /**
     * Generate user-friendly error message for reconnection failure
     *
     * @param \App\Enums\CloudStorageErrorType $errorType The error type
     * @param \Exception $exception The original exception
     * @param array $context Additional context
     * @return string User-friendly error message
     */
    private function generateUserFriendlyErrorMessage(
        \App\Enums\CloudStorageErrorType $errorType, 
        \Exception $exception, 
        array $context
    ): string {
        $provider = $context['provider_display_name'] ?? 'cloud storage';

        return match ($errorType) {
            \App\Enums\CloudStorageErrorType::TOKEN_EXPIRED => 
                "The authorization code has expired. Please try connecting to {$provider} again.",
            
            \App\Enums\CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => 
                "Access was denied. Please ensure you grant all required permissions when connecting to {$provider}.",
            
            \App\Enums\CloudStorageErrorType::INVALID_CREDENTIALS => 
                "Invalid {$provider} configuration. Please contact your administrator to verify the setup.",
            
            \App\Enums\CloudStorageErrorType::NETWORK_ERROR => 
                "Network connection issue prevented the {$provider} connection. Please check your internet connection and try again.",
            
            default => "Failed to connect to {$provider}: " . $exception->getMessage()
        };
    }

    /**
     * Determine if reconnection can be retried for the given error type
     *
     * @param \App\Enums\CloudStorageErrorType $errorType The error type
     * @return bool True if retry is possible
     */
    private function canRetryReconnection(\App\Enums\CloudStorageErrorType $errorType): bool
    {
        return match ($errorType) {
            \App\Enums\CloudStorageErrorType::TOKEN_EXPIRED,
            \App\Enums\CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            \App\Enums\CloudStorageErrorType::NETWORK_ERROR,
            \App\Enums\CloudStorageErrorType::UNKNOWN_ERROR => true,
            
            \App\Enums\CloudStorageErrorType::INVALID_CREDENTIALS => false, // Requires admin intervention
            
            default => true
        };
    }

    /**
     * Get recommended actions for reconnection failure
     *
     * @param \App\Enums\CloudStorageErrorType $errorType The error type
     * @param string $provider The provider name
     * @return array Array of recommended actions
     */
    private function getReconnectionRecommendedActions(\App\Enums\CloudStorageErrorType $errorType, string $provider): array
    {
        $providerDisplay = match ($provider) {
            'google-drive' => 'Google Drive',
            'dropbox' => 'Dropbox',
            'onedrive' => 'OneDrive',
            default => ucfirst(str_replace('-', ' ', $provider))
        };

        return match ($errorType) {
            \App\Enums\CloudStorageErrorType::TOKEN_EXPIRED => [
                'Try connecting again',
                'Ensure you complete the authorization process quickly',
                'Check that your system clock is accurate'
            ],
            
            \App\Enums\CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => [
                'Try connecting again',
                "Grant all requested permissions when prompted by {$providerDisplay}",
                'Ensure your account has the necessary access rights'
            ],
            
            \App\Enums\CloudStorageErrorType::INVALID_CREDENTIALS => [
                'Contact your administrator',
                "Verify {$providerDisplay} application configuration",
                'Check that the client ID and secret are correct'
            ],
            
            \App\Enums\CloudStorageErrorType::NETWORK_ERROR => [
                'Check your internet connection',
                'Try again in a few minutes',
                'Disable any VPN or proxy that might interfere'
            ],
            
            default => [
                'Try connecting again',
                'Contact support if the problem persists',
                'Check the application logs for more details'
            ]
        };
    }
}