<?php

namespace App\Services;

use App\Enums\CloudStorageErrorType;
use Exception;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Support\Facades\Log;

/**
 * Google Drive specific error handler
 * 
 * Extends BaseCloudStorageErrorHandler to provide Google Drive
 * specific error classification, user-friendly messages, and retry logic
 */
class GoogleDriveErrorHandler extends BaseCloudStorageErrorHandler
{

    /**
     * Get the provider name for logging and error messages
     *
     * @return string Provider name
     */
    protected function getProviderName(): string
    {
        return 'Google Drive';
    }

    /**
     * Classify provider-specific exceptions
     *
     * @param Exception $exception The exception to classify
     * @return CloudStorageErrorType|null The classified error type, or null if not handled
     */
    protected function classifyProviderException(Exception $exception): ?CloudStorageErrorType
    {
        // Handle Google Service API exceptions
        if ($exception instanceof GoogleServiceException) {
            return $this->classifyGoogleServiceException($exception);
        }

        return null; // Let base class handle common errors
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
     * Get provider-specific user-friendly messages
     *
     * @param CloudStorageErrorType $type The error type
     * @param array $context Additional context for message generation
     * @return string|null Provider-specific message, or null to use default
     */
    protected function getProviderSpecificMessage(CloudStorageErrorType $type, array $context = []): ?string
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
                ($context['original_message'] ?? 'Please try again or contact support if the problem persists.'),
            
            // Return null for error types that should use common messages
            default => null
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

        return 3600; // 1 hour for Google Drive quota issues
    }



    /**
     * Get provider-specific recommended actions
     *
     * @param CloudStorageErrorType $type The error type
     * @param array $context Additional context for recommendations
     * @return array|null Provider-specific actions, or null to use default
     */
    protected function getProviderSpecificActions(CloudStorageErrorType $type, array $context = []): ?array
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
            
            // Return null for error types that should use common actions
            default => null
        };
    }
}