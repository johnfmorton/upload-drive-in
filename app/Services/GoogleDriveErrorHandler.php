<?php

namespace App\Services;

use App\Contracts\CloudStorageErrorHandlerInterface;
use App\Enums\CloudStorageErrorType;
use Exception;
use Google\Service\Exception as GoogleServiceException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Google Drive specific error handler
 * 
 * Implements CloudStorageErrorHandlerInterface to provide Google Drive
 * specific error classification, user-friendly messages, and retry logic
 */
class GoogleDriveErrorHandler implements CloudStorageErrorHandlerInterface
{
    private const PROVIDER_NAME = 'Google Drive';

    /**
     * Classify an exception into a universal error type
     *
     * @param Exception $exception The exception to classify
     * @return CloudStorageErrorType The classified error type
     */
    public function classifyError(Exception $exception): CloudStorageErrorType
    {
        Log::debug('Classifying Google Drive error', [
            'exception_type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode()
        ]);

        // Handle Google Service API exceptions
        if ($exception instanceof GoogleServiceException) {
            return $this->classifyGoogleServiceException($exception);
        }

        // Handle network-related exceptions
        if ($this->isNetworkError($exception)) {
            return CloudStorageErrorType::NETWORK_ERROR;
        }

        // Handle timeout exceptions
        if ($this->isTimeoutError($exception)) {
            return CloudStorageErrorType::TIMEOUT;
        }

        // Default to unknown error
        Log::warning('Unclassified Google Drive error', [
            'exception_type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode()
        ]);

        return CloudStorageErrorType::UNKNOWN_ERROR;
    }

    /**
     * Classify Google Service API exceptions
     *
     * @param GoogleServiceException $exception
     * @return CloudStorageErrorType
     */
    private function classifyGoogleServiceException(GoogleServiceException $exception): CloudStorageErrorType
    {
        $code = $exception->getCode();
        $errors = $exception->getErrors();
        $reason = $errors[0]['reason'] ?? null;
        $message = strtolower($exception->getMessage());

        Log::debug('Classifying Google Service exception', [
            'code' => $code,
            'reason' => $reason,
            'errors' => $errors,
            'message' => $message
        ]);

        return match ($code) {
            401 => $this->classifyUnauthorizedError($exception, $reason, $message),
            403 => $this->classifyForbiddenError($exception, $reason, $message),
            404 => CloudStorageErrorType::FILE_NOT_FOUND,
            413 => CloudStorageErrorType::FILE_TOO_LARGE,
            429 => CloudStorageErrorType::API_QUOTA_EXCEEDED,
            500, 502, 503 => CloudStorageErrorType::SERVICE_UNAVAILABLE,
            default => $this->classifyByReason($reason) ?? CloudStorageErrorType::UNKNOWN_ERROR
        };
    }

    /**
     * Classify 401 Unauthorized errors
     *
     * @param GoogleServiceException $exception
     * @param string|null $reason
     * @param string $message
     * @return CloudStorageErrorType
     */
    private function classifyUnauthorizedError(
        GoogleServiceException $exception,
        ?string $reason,
        string $message
    ): CloudStorageErrorType {
        if ($reason === 'authError' || str_contains($message, 'invalid_grant')) {
            return CloudStorageErrorType::TOKEN_EXPIRED;
        }

        if (str_contains($message, 'credentials') || str_contains($message, 'client')) {
            return CloudStorageErrorType::INVALID_CREDENTIALS;
        }

        return CloudStorageErrorType::TOKEN_EXPIRED; // Default for 401 errors
    }

    /**
     * Classify 403 Forbidden errors
     *
     * @param GoogleServiceException $exception
     * @param string|null $reason
     * @param string $message
     * @return CloudStorageErrorType
     */
    private function classifyForbiddenError(
        GoogleServiceException $exception,
        ?string $reason,
        string $message
    ): CloudStorageErrorType {
        if ($reason === 'insufficientPermissions' || str_contains($message, 'insufficient')) {
            return CloudStorageErrorType::INSUFFICIENT_PERMISSIONS;
        }

        if ($reason === 'quotaExceeded' || $reason === 'storageQuotaExceeded' || str_contains($message, 'quota')) {
            return CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED;
        }

        if ($reason === 'rateLimitExceeded' || $reason === 'userRateLimitExceeded') {
            return CloudStorageErrorType::API_QUOTA_EXCEEDED;
        }

        if (str_contains($message, 'folder') || str_contains($message, 'directory')) {
            return CloudStorageErrorType::FOLDER_ACCESS_DENIED;
        }

        return CloudStorageErrorType::INSUFFICIENT_PERMISSIONS; // Default for 403 errors
    }

    /**
     * Classify error by Google API reason code
     *
     * @param string|null $reason
     * @return CloudStorageErrorType|null
     */
    private function classifyByReason(?string $reason): ?CloudStorageErrorType
    {
        if (!$reason) {
            return null;
        }

        return match ($reason) {
            'notFound' => CloudStorageErrorType::FILE_NOT_FOUND,
            'authError', 'unauthorized' => CloudStorageErrorType::TOKEN_EXPIRED,
            'insufficientPermissions' => CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            'quotaExceeded', 'rateLimitExceeded', 'userRateLimitExceeded' => CloudStorageErrorType::API_QUOTA_EXCEEDED,
            'storageQuotaExceeded' => CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            'backendError', 'internalError', 'serviceUnavailable' => CloudStorageErrorType::SERVICE_UNAVAILABLE,
            'invalidFileType' => CloudStorageErrorType::INVALID_FILE_TYPE,
            'fileTooLarge' => CloudStorageErrorType::FILE_TOO_LARGE,
            default => null
        };
    }

    /**
     * Check if exception is a network error
     *
     * @param Exception $exception
     * @return bool
     */
    private function isNetworkError(Exception $exception): bool
    {
        if ($exception instanceof ConnectException) {
            return true;
        }

        if ($exception instanceof RequestException) {
            $message = strtolower($exception->getMessage());
            return str_contains($message, 'connection') ||
                   str_contains($message, 'network') ||
                   str_contains($message, 'dns') ||
                   str_contains($message, 'resolve');
        }

        $message = strtolower($exception->getMessage());
        return str_contains($message, 'connection refused') ||
               str_contains($message, 'network unreachable') ||
               str_contains($message, 'name resolution failed');
    }

    /**
     * Check if exception is a timeout error
     *
     * @param Exception $exception
     * @return bool
     */
    private function isTimeoutError(Exception $exception): bool
    {
        $message = strtolower($exception->getMessage());
        return str_contains($message, 'timeout') ||
               str_contains($message, 'timed out') ||
               str_contains($message, 'operation timeout');
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
        $fileName = $context['file_name'] ?? 'file';
        $operation = $context['operation'] ?? 'operation';

        return match ($type) {
            CloudStorageErrorType::TOKEN_EXPIRED => 
                'Your Google Drive connection has expired. Please reconnect your Google Drive account to continue uploading files.',
            
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => 
                'Insufficient Google Drive permissions. Please reconnect your account and ensure you grant full access to Google Drive.',
            
            CloudStorageErrorType::API_QUOTA_EXCEEDED => 
                'Google Drive API limit reached. Your uploads will resume automatically in ' . 
                $this->getQuotaResetTimeMessage($context) . '. No action is required.',
            
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED => 
                'Your Google Drive storage is full. Please free up space in your Google Drive account or upgrade your storage plan.',
            
            CloudStorageErrorType::FILE_NOT_FOUND => 
                "The file '{$fileName}' could not be found in Google Drive. It may have been deleted or moved.",
            
            CloudStorageErrorType::FOLDER_ACCESS_DENIED => 
                'Access denied to the Google Drive folder. Please check your folder permissions or reconnect your account.',
            
            CloudStorageErrorType::INVALID_FILE_TYPE => 
                "The file type of '{$fileName}' is not supported by Google Drive. Please try a different file format.",
            
            CloudStorageErrorType::FILE_TOO_LARGE => 
                "The file '{$fileName}' is too large for Google Drive. Maximum file size is 5TB for most file types.",
            
            CloudStorageErrorType::NETWORK_ERROR => 
                'Network connection issue prevented the Google Drive upload. The upload will be retried automatically.',
            
            CloudStorageErrorType::SERVICE_UNAVAILABLE => 
                'Google Drive is temporarily unavailable. Your uploads will be retried automatically when the service is restored.',
            
            CloudStorageErrorType::INVALID_CREDENTIALS => 
                'Invalid Google Drive credentials. Please reconnect your Google Drive account in the settings.',
            
            CloudStorageErrorType::TIMEOUT => 
                "The Google Drive {$operation} timed out. This is usually temporary and will be retried automatically.",
            
            CloudStorageErrorType::INVALID_FILE_CONTENT => 
                "The file '{$fileName}' appears to be corrupted or has invalid content. Please try uploading the file again.",
            
            CloudStorageErrorType::UNKNOWN_ERROR => 
                'An unexpected error occurred with Google Drive. ' . 
                ($context['original_message'] ?? 'Please try again or contact support if the problem persists.')
        };
    }

    /**
     * Get quota reset time message
     *
     * @param array $context
     * @return string
     */
    private function getQuotaResetTimeMessage(array $context): string
    {
        if (isset($context['retry_after'])) {
            $minutes = ceil($context['retry_after'] / 60);
            return $minutes <= 60 ? "{$minutes} minutes" : ceil($minutes / 60) . ' hours';
        }

        return '1 hour'; // Default estimate
    }

    /**
     * Determine if an error should be retried based on type and attempt count
     *
     * @param CloudStorageErrorType $type The error type
     * @param int $attemptCount Current attempt count (1-based)
     * @return bool True if the operation should be retried
     */
    public function shouldRetry(CloudStorageErrorType $type, int $attemptCount): bool
    {
        $maxAttempts = $this->getMaxRetryAttempts($type);
        
        if ($attemptCount >= $maxAttempts) {
            return false;
        }

        return match ($type) {
            // Never retry - requires user intervention
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            CloudStorageErrorType::INVALID_CREDENTIALS,
            CloudStorageErrorType::INVALID_FILE_TYPE,
            CloudStorageErrorType::FILE_TOO_LARGE,
            CloudStorageErrorType::INVALID_FILE_CONTENT => false,
            
            // Retry with limits
            CloudStorageErrorType::API_QUOTA_EXCEEDED => false, // Don't retry immediately, wait for quota reset
            CloudStorageErrorType::NETWORK_ERROR,
            CloudStorageErrorType::SERVICE_UNAVAILABLE,
            CloudStorageErrorType::TIMEOUT => $attemptCount < 3, // Retry up to 3 times
            
            // Don't retry permanent failures
            CloudStorageErrorType::FILE_NOT_FOUND,
            CloudStorageErrorType::FOLDER_ACCESS_DENIED => false,
            
            // Conservative retry for unknown errors
            CloudStorageErrorType::UNKNOWN_ERROR => $attemptCount < 2
        };
    }

    /**
     * Get the delay in seconds before retrying an operation
     *
     * @param CloudStorageErrorType $type The error type
     * @param int $attemptCount Current attempt count (1-based)
     * @return int Delay in seconds before retry
     */
    public function getRetryDelay(CloudStorageErrorType $type, int $attemptCount): int
    {
        return match ($type) {
            CloudStorageErrorType::API_QUOTA_EXCEEDED => 3600, // 1 hour for quota issues
            CloudStorageErrorType::NETWORK_ERROR => min(300, 30 * pow(2, $attemptCount - 1)), // Exponential backoff, max 5 minutes
            CloudStorageErrorType::SERVICE_UNAVAILABLE => min(1800, 60 * pow(2, $attemptCount - 1)), // Exponential backoff, max 30 minutes
            CloudStorageErrorType::TIMEOUT => min(600, 60 * $attemptCount), // Linear backoff, max 10 minutes
            default => min(300, 30 * $attemptCount) // Linear backoff for unknown errors, max 5 minutes
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
            // No retries for user intervention required
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            CloudStorageErrorType::INVALID_CREDENTIALS,
            CloudStorageErrorType::INVALID_FILE_TYPE,
            CloudStorageErrorType::FILE_TOO_LARGE,
            CloudStorageErrorType::INVALID_FILE_CONTENT,
            CloudStorageErrorType::FILE_NOT_FOUND,
            CloudStorageErrorType::FOLDER_ACCESS_DENIED => 0,
            
            // Limited retries for quota issues
            CloudStorageErrorType::API_QUOTA_EXCEEDED => 0, // No immediate retries, handled by queue delay
            
            // Standard retries for transient issues
            CloudStorageErrorType::NETWORK_ERROR,
            CloudStorageErrorType::SERVICE_UNAVAILABLE,
            CloudStorageErrorType::TIMEOUT => 3,
            
            // Conservative retries for unknown errors
            CloudStorageErrorType::UNKNOWN_ERROR => 1
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
        return $type->requiresUserIntervention();
    }

    /**
     * Get recommended actions for the user to resolve the error
     *
     * @param CloudStorageErrorType $type The error type
     * @param array $context Additional context for recommendations
     * @return array Array of recommended actions
     */
    public function getRecommendedActions(CloudStorageErrorType $type, array $context = []): array
    {
        return match ($type) {
            CloudStorageErrorType::TOKEN_EXPIRED => [
                'Go to Settings → Cloud Storage',
                'Click "Reconnect Google Drive"',
                'Complete the authorization process',
                'Retry your upload'
            ],
            
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => [
                'Go to Settings → Cloud Storage',
                'Click "Reconnect Google Drive"',
                'Ensure you grant full access when prompted',
                'Check that you have edit permissions for the target folder'
            ],
            
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED => [
                'Free up space in your Google Drive account',
                'Empty your Google Drive trash',
                'Consider upgrading your Google Drive storage plan',
                'Contact your administrator if using a business account'
            ],
            
            CloudStorageErrorType::API_QUOTA_EXCEEDED => [
                'Wait for the quota to reset (usually within an hour)',
                'Uploads will resume automatically',
                'Consider spreading uploads across multiple days for large batches'
            ],
            
            CloudStorageErrorType::INVALID_CREDENTIALS => [
                'Go to Settings → Cloud Storage',
                'Disconnect and reconnect your Google Drive account',
                'Ensure your Google account is active and accessible'
            ],
            
            CloudStorageErrorType::FOLDER_ACCESS_DENIED => [
                'Check that the target folder exists in your Google Drive',
                'Verify you have write permissions to the folder',
                'Try reconnecting your Google Drive account'
            ],
            
            CloudStorageErrorType::INVALID_FILE_TYPE => [
                'Convert the file to a supported format',
                'Check Google Drive\'s supported file types',
                'Try uploading a different file to test'
            ],
            
            CloudStorageErrorType::FILE_TOO_LARGE => [
                'Compress the file to reduce its size',
                'Split large files into smaller parts',
                'Use Google Drive\'s web interface for very large files'
            ],
            
            default => [
                'Try uploading the file again',
                'Check your internet connection',
                'Contact support if the problem persists'
            ]
        };
    }
}