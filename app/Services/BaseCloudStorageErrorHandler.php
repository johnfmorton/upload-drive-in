<?php

namespace App\Services;

use App\Contracts\CloudStorageErrorHandlerInterface;
use App\Enums\CloudStorageErrorType;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Base cloud storage error handler
 * 
 * Provides common error handling logic that can be shared
 * across all cloud storage providers. Provider-specific handlers
 * can extend this class to add their own error classification.
 */
abstract class BaseCloudStorageErrorHandler implements CloudStorageErrorHandlerInterface
{
    /**
     * Get the provider name for logging and error messages
     *
     * @return string
     */
    abstract protected function getProviderName(): string;

    /**
     * Classify provider-specific exceptions
     * 
     * This method should be implemented by each provider
     * to handle their specific exception types and error codes.
     *
     * @param Exception $exception The exception to classify
     * @return CloudStorageErrorType|null The classified error type, or null if not handled
     */
    abstract protected function classifyProviderException(Exception $exception): ?CloudStorageErrorType;

    /**
     * Get provider-specific user-friendly messages
     * 
     * This method should be implemented by each provider
     * to provide user-friendly error messages for their specific error types.
     *
     * @param CloudStorageErrorType $type The error type
     * @param array $context Additional context for message generation
     * @return string|null Provider-specific message, or null to use default
     */
    abstract protected function getProviderSpecificMessage(CloudStorageErrorType $type, array $context = []): ?string;

    /**
     * Get provider-specific recommended actions
     * 
     * This method should be implemented by each provider
     * to provide recommended actions for their specific error types.
     *
     * @param CloudStorageErrorType $type The error type
     * @param array $context Additional context for recommendations
     * @return array|null Provider-specific actions, or null to use default
     */
    abstract protected function getProviderSpecificActions(CloudStorageErrorType $type, array $context = []): ?array;

    /**
     * Classify a universal error into an exception
     * 
     * @param Exception $exception The exception to classify
     * @return CloudStorageErrorType The classified error type
     */
    public function classifyError(Exception $exception): CloudStorageErrorType
    {
        // First try provider-specific classification
        $providerResult = $this->classifyProviderException($exception);
        if ($providerResult !== null) {
            return $providerResult;
        }

        // Fall back to common error classification
        return $this->classifyCommonError($exception);
    }

    /**
     * Classify common errors that apply to all providers
     * 
     * @param Exception $exception The exception to classify
     * @return CloudStorageErrorType The classified error type
     */
    protected function classifyCommonError(Exception $exception): CloudStorageErrorType
    {
        // Handle network-related exceptions
        if ($this->isNetworkError($exception)) {
            return CloudStorageErrorType::NETWORK_ERROR;
        }

        // Handle timeout errors
        if ($this->isTimeoutError($exception)) {
            return CloudStorageErrorType::TIMEOUT;
        }

        Log::debug('Classifying error for ' . $this->getProviderName(), [
            'exception_type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode()
        ]);

        // Default to unknown error
        Log::warning('Unclassified error for ' . $this->getProviderName(), [
            'exception_type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode()
        ]);

        return CloudStorageErrorType::UNKNOWN_ERROR;
    }

    /**
     * Check if exception is a network error
     *
     * @param Exception $exception
     * @return bool
     */
    protected function isNetworkError(Exception $exception): bool
    {
        if ($exception instanceof ConnectException) {
            return true;
        }

        if ($exception instanceof RequestException) {
            return true;
        }

        $message = strtolower($exception->getMessage());
        return str_contains($message, 'connection') ||
               str_contains($message, 'network') ||
               str_contains($message, 'dns') ||
               str_contains($message, 'resolve') ||
               str_contains($message, 'unreachable') ||
               str_contains($message, 'connection refused') ||
               str_contains($message, 'name resolution failed') ||
               str_contains($message, 'could not resolve host');
    }

    /**
     * Check if exception is a timeout error
     *
     * @param Exception $exception
     * @return bool
     */
    protected function isTimeoutError(Exception $exception): bool
    {
        $message = strtolower($exception->getMessage());
        return str_contains($message, 'timeout') ||
               str_contains($message, 'timed out') ||
               str_contains($message, 'operation timeout') ||
               str_contains($message, 'request timeout');
    }

    /**
     * Generate a user-friendly error message for the given error type
     *
     * @param CloudStorageErrorType $type The error type
     * @param array $context Additional context for message generation
     * @return string User-friendly error message
     */
    public function getUserFriendlyMessage(CloudStorageErrorType $type, array $context = []): string
    {
        // First try provider-specific message
        $providerMessage = $this->getProviderSpecificMessage($type, $context);
        if ($providerMessage !== null) {
            return $providerMessage;
        }

        // Fall back to common messages
        return $this->getCommonUserFriendlyMessage($type, $context);
    }

    /**
     * Get common user-friendly error messages
     *
     * @param CloudStorageErrorType $type The error type
     * @param array $context Additional context for message generation
     * @return string User-friendly error message
     */
    protected function getCommonUserFriendlyMessage(CloudStorageErrorType $type, array $context = []): string
    {
        $providerName = $this->getProviderName();
        $fileName = $context['file_name'] ?? 'file';
        $operation = $context['operation'] ?? 'operation';

        return match ($type) {
            CloudStorageErrorType::NETWORK_ERROR => "Network connection issue prevented the {$operation}. The upload will be retried automatically.",
            CloudStorageErrorType::SERVICE_UNAVAILABLE => "The {$providerName} service is temporarily unavailable. Your uploads will be retried automatically.",
            CloudStorageErrorType::TIMEOUT => "The {$operation} with {$providerName} timed out. This is usually temporary and will be retried automatically.",
            CloudStorageErrorType::INVALID_FILE_CONTENT => "The file '{$fileName}' appears to be corrupted or has invalid content. Please try uploading the file again.",
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED => "The {$providerName} provider is not configured. Please reconfigure your credentials in the cloud storage settings.",
            CloudStorageErrorType::PROVIDER_INITIALIZATION_FAILED => "Failed to initialize {$providerName}. Please check your credentials and connection.",
            CloudStorageErrorType::FEATURE_NOT_SUPPORTED => "The requested feature is not available with {$providerName}. Consider using a different cloud storage provider.",
            default => "An unexpected error occurred with {$providerName}. " . ($context['original_message'] ?? 'Please try again or contact support if the problem persists.')
        };
    }

    /**
     * Get recommended actions for the user to resolve an error
     *
     * @param CloudStorageErrorType $type The error type
     * @param array $context Additional context for recommendations
     * @return array Array of recommended actions
     */
    public function getRecommendedActions(CloudStorageErrorType $type, array $context = []): array
    {
        // First try provider-specific actions
        $providerActions = $this->getProviderSpecificActions($type, $context);
        if ($providerActions !== null) {
            return $providerActions;
        }

        // Fall back to common actions
        return $this->getCommonRecommendedActions($type, $context);
    }

    /**
     * Get common recommended actions for error types
     *
     * @param CloudStorageErrorType $type The error type
     * @param array $context Additional context for recommendations
     * @return array Array of recommended actions
     */
    protected function getCommonRecommendedActions(CloudStorageErrorType $type, array $context = []): array
    {
        $providerName = $this->getProviderName();

        return match ($type) {
            CloudStorageErrorType::NETWORK_ERROR => [
                'Check your internet connection',
                'Try the operation again',
                'Contact support if the problem persists'
            ],
            CloudStorageErrorType::SERVICE_UNAVAILABLE => [
                'Wait a few minutes and try again',
                'Check the {$providerName} service status',
                'Contact support if the problem persists'
            ],
            CloudStorageErrorType::TIMEOUT => [
                'Try the operation again',
                'Check your internet connection',
                'Contact support if timeouts persist'
            ],
            CloudStorageErrorType::INVALID_FILE_CONTENT => [
                'Check that the file is not corrupted',
                'Try uploading the file again',
                'Verify the file format is supported'
            ],
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED => [
                'Configure your {$providerName} credentials',
                'Go to Settings → Cloud Storage',
                'Test the connection after configuration'
            ],
            CloudStorageErrorType::PROVIDER_INITIALIZATION_FAILED => [
                'Verify your credentials are correct and active',
                'Test the connection in settings',
                'Check your {$providerName} configuration'
            ],
            CloudStorageErrorType::FEATURE_NOT_SUPPORTED => [
                'Consider using a different cloud storage provider',
                'Contact support for alternative solutions'
            ],
            default => [
                'Try the operation again',
                'Contact support if the problem persists'
            ]
        };
    }

    /**
     * Determine if an error requires user intervention
     *
     * @param CloudStorageErrorType $type The error type
     * @return bool True if user intervention is required
     */
    public function requiresUserIntervention(CloudStorageErrorType $type): bool
    {
        return match ($type) {
            CloudStorageErrorType::INVALID_CREDENTIALS,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            CloudStorageErrorType::INVALID_FILE_TYPE,
            CloudStorageErrorType::FILE_TOO_LARGE,
            CloudStorageErrorType::INVALID_FILE_CONTENT,
            CloudStorageErrorType::FOLDER_ACCESS_DENIED,
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED,
            CloudStorageErrorType::FEATURE_NOT_SUPPORTED => true,
            default => false
        };
    }

    /**
     * Determine if an error should be retried
     *
     * @param CloudStorageErrorType $type The error type
     * @param int $attemptCount Current attempt count (1-based)
     * @return bool True if operation should be retried
     */
    public function shouldRetry(CloudStorageErrorType $type, int $attemptCount): bool
    {
        $maxAttempts = $this->getMaxRetryAttempts($type);
        if ($attemptCount >= $maxAttempts) {
            return false;
        }

        return match ($type) {
            CloudStorageErrorType::NETWORK_ERROR,
            CloudStorageErrorType::SERVICE_UNAVAILABLE,
            CloudStorageErrorType::TIMEOUT,
            CloudStorageErrorType::API_QUOTA_EXCEEDED => true,
            // Provider-specific errors that shouldn't be retried
            CloudStorageErrorType::INVALID_CREDENTIALS,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            CloudStorageErrorType::INVALID_FILE_TYPE,
            CloudStorageErrorType::FILE_TOO_LARGE,
            CloudStorageErrorType::INVALID_FILE_CONTENT,
            CloudStorageErrorType::FOLDER_ACCESS_DENIED,
            CloudStorageErrorType::FILE_NOT_FOUND,
            CloudStorageErrorType::STORAGE_CLASS_NOT_SUPPORTED,
            CloudStorageErrorType::INVALID_REGION,
            CloudStorageErrorType::BUCKET_ACCESS_DENIED,
            CloudStorageErrorType::INVALID_BUCKET_NAME,
            CloudStorageErrorType::BUCKET_NOT_FOUND,
            CloudStorageErrorType::FEATURE_NOT_SUPPORTED,
            CloudStorageErrorType::PROVIDER_INITIALIZATION_FAILED,
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED => false,
            // Conservative retry for unknown errors
            CloudStorageErrorType::UNKNOWN_ERROR => $attemptCount < 2,
            default => false
        };
    }

    /**
     * Get the maximum number of retry attempts for an error type
     *
     * @param CloudStorageErrorType $type The error type
     * @return int Maximum retry attempts
     */
    public function getMaxRetryAttempts(CloudStorageErrorType $type): int
    {
        return match ($type) {
            CloudStorageErrorType::API_QUOTA_EXCEEDED => 3600, // Default 1 hour
            CloudStorageErrorType::NETWORK_ERROR,
            CloudStorageErrorType::SERVICE_UNAVAILABLE,
            CloudStorageErrorType::TIMEOUT => 3,
            CloudStorageErrorType::UNKNOWN_ERROR => 1,
            default => 0 // No retry for other errors
        };
    }

    /**
     * Get the delay before retry
     *
     * @param CloudStorageErrorType $type The error type
     * @param int $attemptCount Current attempt count (1-based)
     * @param array $context Additional context
     * @return int Delay in seconds before retry
     */
    public function getRetryDelay(CloudStorageErrorType $type, int $attemptCount, array $context = []): int
    {
        return match ($type) {
            CloudStorageErrorType::API_QUOTA_EXCEEDED => $this->getQuotaRetryDelay($context),
            CloudStorageErrorType::NETWORK_ERROR => min(30, 30 * pow(2, $attemptCount - 1)), // Exponential backoff, max 30 minutes
            CloudStorageErrorType::SERVICE_UNAVAILABLE => min(1800, 60 * pow(2, $attemptCount - 1)), // Exponential backoff, max 30 minutes
            CloudStorageErrorType::TIMEOUT => min(300, 60 * $attemptCount), // Linear backoff, max 5 minutes
            default => min(300, 30 * $attemptCount) // Linear backoff for unknown errors, max 5 minutes
        };
    }

    /**
     * Get provider-specific quota retry delay
     *
     * @param array $context Additional context
     * @return int Delay in seconds
     */
    protected function getQuotaRetryDelay(array $context = []): int
    {
        if (isset($context['retry_after'])) {
            return (int) $context['retry_after'];
        }

        // Default to 10 minutes
        return 600;
    }

    /**
     * Get quota reset time message for user-friendly display
     *
     * @param array $context Additional context
     * @return string
     */
    protected function getQuotaResetTimeMessage(array $context = []): string
    {
        if (isset($context['retry_after'])) {
            $minutes = ceil($context['retry_after'] / 60);
            return $minutes <= 60 ? "{$minutes} minutes" : ceil($minutes / 60) . ' hours';
        }

        return '10 minutes'; // Default estimate
    }
}