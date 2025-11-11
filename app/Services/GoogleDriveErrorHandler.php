<?php

namespace App\Services;

use App\Enums\CloudStorageErrorType;
use App\Enums\TokenRefreshErrorType;
use Exception;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Support\Facades\Log;
use Throwable;

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
     * @param Throwable $exception The exception to classify
     * @return CloudStorageErrorType|null The classified error type, or null if not handled
     */
    protected function classifyProviderException(Throwable $exception): ?CloudStorageErrorType
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
                __('messages.google_drive_error_token_expired'),
            
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => 
                __('messages.google_drive_error_insufficient_permissions'),
            
            CloudStorageErrorType::API_QUOTA_EXCEEDED => 
                __('messages.google_drive_error_api_quota_exceeded', [
                    'time' => $this->getQuotaResetTimeMessage($context)
                ]),
            
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED => 
                __('messages.google_drive_error_storage_quota_exceeded'),
            
            CloudStorageErrorType::FILE_NOT_FOUND => 
                __('messages.google_drive_error_file_not_found', ['filename' => $fileName]),
            
            CloudStorageErrorType::FOLDER_ACCESS_DENIED => 
                __('messages.google_drive_error_folder_access_denied'),
            
            CloudStorageErrorType::INVALID_FILE_TYPE => 
                __('messages.google_drive_error_invalid_file_type', ['filename' => $fileName]),
            
            CloudStorageErrorType::FILE_TOO_LARGE => 
                __('messages.google_drive_error_file_too_large', ['filename' => $fileName]),
            
            CloudStorageErrorType::NETWORK_ERROR => 
                __('messages.google_drive_error_network_error'),
            
            CloudStorageErrorType::SERVICE_UNAVAILABLE => 
                __('messages.google_drive_error_service_unavailable'),
            
            CloudStorageErrorType::INVALID_CREDENTIALS => 
                __('messages.google_drive_error_invalid_credentials'),
            
            CloudStorageErrorType::TIMEOUT => 
                __('messages.google_drive_error_timeout', ['operation' => $operation]),
            
            CloudStorageErrorType::INVALID_FILE_CONTENT => 
                __('messages.google_drive_error_invalid_file_content', ['filename' => $fileName]),
            
            CloudStorageErrorType::UNKNOWN_ERROR => 
                __('messages.google_drive_error_unknown_error', [
                    'message' => $context['original_message'] ?? __('messages.error_generic')
                ]),
            
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
     * Get a human-readable quota reset time message
     *
     * @param array $context Additional context
     * @return string Formatted time message
     */
    protected function getQuotaResetTimeMessage(array $context = []): string
    {
        $delay = $this->getQuotaRetryDelay($context);
        
        if ($delay >= 3600) {
            $hours = intval($delay / 3600);
            return $hours === 1 
                ? __('messages.quota_reset_time_1_hour')
                : __('messages.quota_reset_time_hours', ['hours' => $hours]);
        }
        
        if ($delay >= 60) {
            $minutes = intval($delay / 60);
            return __('messages.quota_reset_time_minutes', ['minutes' => $minutes]);
        }
        
        return __('messages.quota_reset_time_unknown');
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
                __('messages.google_drive_action_token_expired_1'),
                __('messages.google_drive_action_token_expired_2'),
                __('messages.google_drive_action_token_expired_3'),
                __('messages.google_drive_action_token_expired_4')
            ],
            
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => [
                __('messages.google_drive_action_insufficient_permissions_1'),
                __('messages.google_drive_action_insufficient_permissions_2'),
                __('messages.google_drive_action_insufficient_permissions_3'),
                __('messages.google_drive_action_insufficient_permissions_4')
            ],
            
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED => [
                __('messages.google_drive_action_storage_quota_exceeded_1'),
                __('messages.google_drive_action_storage_quota_exceeded_2'),
                __('messages.google_drive_action_storage_quota_exceeded_3'),
                __('messages.google_drive_action_storage_quota_exceeded_4')
            ],
            
            CloudStorageErrorType::API_QUOTA_EXCEEDED => [
                __('messages.google_drive_action_api_quota_exceeded_1'),
                __('messages.google_drive_action_api_quota_exceeded_2'),
                __('messages.google_drive_action_api_quota_exceeded_3')
            ],
            
            CloudStorageErrorType::INVALID_CREDENTIALS => [
                __('messages.google_drive_action_invalid_credentials_1'),
                __('messages.google_drive_action_invalid_credentials_2'),
                __('messages.google_drive_action_invalid_credentials_3')
            ],
            
            CloudStorageErrorType::FOLDER_ACCESS_DENIED => [
                __('messages.google_drive_action_folder_access_denied_1'),
                __('messages.google_drive_action_folder_access_denied_2'),
                __('messages.google_drive_action_folder_access_denied_3')
            ],
            
            CloudStorageErrorType::INVALID_FILE_TYPE => [
                __('messages.google_drive_action_invalid_file_type_1'),
                __('messages.google_drive_action_invalid_file_type_2'),
                __('messages.google_drive_action_invalid_file_type_3')
            ],
            
            CloudStorageErrorType::FILE_TOO_LARGE => [
                __('messages.google_drive_action_file_too_large_1'),
                __('messages.google_drive_action_file_too_large_2'),
                __('messages.google_drive_action_file_too_large_3')
            ],
            
            // Return null for error types that should use common actions
            default => null
        };
    }

    /**
     * Classify token refresh specific errors
     *
     * Maps Google API exceptions to TokenRefreshErrorType for token refresh operations
     *
     * @param Exception $exception The exception that occurred during token refresh
     * @return TokenRefreshErrorType The classified token refresh error type
     */
    public function classifyTokenRefreshError(Exception $exception): TokenRefreshErrorType
    {
        Log::debug('Classifying token refresh error', [
            'exception_type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode()
        ]);

        // Handle Google Service API exceptions
        if ($exception instanceof GoogleServiceException) {
            return $this->classifyGoogleTokenRefreshException($exception);
        }

        // Handle network/timeout exceptions
        if ($this->isNetworkTimeoutException($exception)) {
            return TokenRefreshErrorType::NETWORK_TIMEOUT;
        }

        // Default to unknown error for unclassified exceptions
        Log::warning('Unclassified token refresh error', [
            'exception_type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode()
        ]);

        return TokenRefreshErrorType::UNKNOWN_ERROR;
    }

    /**
     * Classify Google Service API exceptions for token refresh operations
     *
     * @param GoogleServiceException $exception
     * @return TokenRefreshErrorType
     */
    private function classifyGoogleTokenRefreshException(GoogleServiceException $exception): TokenRefreshErrorType
    {
        $code = $exception->getCode();
        $errors = $exception->getErrors();
        $reason = $errors[0]['reason'] ?? null;
        $message = strtolower($exception->getMessage());

        Log::debug('Classifying Google token refresh exception', [
            'code' => $code,
            'reason' => $reason,
            'errors' => $errors,
            'message' => $message
        ]);

        return match ($code) {
            400 => $this->classifyBadRequestTokenError($exception, $reason, $message),
            401 => $this->classifyUnauthorizedTokenError($exception, $reason, $message),
            403 => $this->classifyForbiddenTokenError($exception, $reason, $message),
            429 => TokenRefreshErrorType::API_QUOTA_EXCEEDED,
            500, 502, 503, 504 => TokenRefreshErrorType::SERVICE_UNAVAILABLE,
            default => $this->classifyTokenErrorByReason($reason) ?? TokenRefreshErrorType::UNKNOWN_ERROR
        };
    }

    /**
     * Classify 400 Bad Request errors for token refresh
     *
     * @param GoogleServiceException $exception
     * @param string|null $reason
     * @param string $message
     * @return TokenRefreshErrorType
     */
    private function classifyBadRequestTokenError(
        GoogleServiceException $exception,
        ?string $reason,
        string $message
    ): TokenRefreshErrorType {
        if (str_contains($message, 'invalid_grant') || str_contains($message, 'invalid_request')) {
            return TokenRefreshErrorType::INVALID_REFRESH_TOKEN;
        }

        if (str_contains($message, 'expired') || str_contains($message, 'token_expired')) {
            return TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN;
        }

        return TokenRefreshErrorType::INVALID_REFRESH_TOKEN; // Default for 400 errors
    }

    /**
     * Classify 401 Unauthorized errors for token refresh
     *
     * @param GoogleServiceException $exception
     * @param string|null $reason
     * @param string $message
     * @return TokenRefreshErrorType
     */
    private function classifyUnauthorizedTokenError(
        GoogleServiceException $exception,
        ?string $reason,
        string $message
    ): TokenRefreshErrorType {
        if ($reason === 'authError' || str_contains($message, 'invalid_grant')) {
            return TokenRefreshErrorType::INVALID_REFRESH_TOKEN;
        }

        if (str_contains($message, 'expired') || str_contains($message, 'token_expired')) {
            return TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN;
        }

        return TokenRefreshErrorType::INVALID_REFRESH_TOKEN; // Default for 401 errors
    }

    /**
     * Classify 403 Forbidden errors for token refresh
     *
     * @param GoogleServiceException $exception
     * @param string|null $reason
     * @param string $message
     * @return TokenRefreshErrorType
     */
    private function classifyForbiddenTokenError(
        GoogleServiceException $exception,
        ?string $reason,
        string $message
    ): TokenRefreshErrorType {
        if ($reason === 'rateLimitExceeded' || $reason === 'userRateLimitExceeded' || str_contains($message, 'quota')) {
            return TokenRefreshErrorType::API_QUOTA_EXCEEDED;
        }

        // Most 403 errors during token refresh indicate invalid tokens
        return TokenRefreshErrorType::INVALID_REFRESH_TOKEN;
    }

    /**
     * Classify token refresh error by Google API reason code
     *
     * @param string|null $reason
     * @return TokenRefreshErrorType|null
     */
    private function classifyTokenErrorByReason(?string $reason): ?TokenRefreshErrorType
    {
        if (!$reason) {
            return null;
        }

        return match ($reason) {
            'invalid_grant', 'invalid_token', 'unauthorized' => TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
            'token_expired', 'expired_token' => TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN,
            'quotaExceeded', 'rateLimitExceeded', 'userRateLimitExceeded' => TokenRefreshErrorType::API_QUOTA_EXCEEDED,
            'backendError', 'internalError', 'serviceUnavailable' => TokenRefreshErrorType::SERVICE_UNAVAILABLE,
            default => null
        };
    }

    /**
     * Check if an exception is a network timeout error
     *
     * @param Exception $exception
     * @return bool
     */
    private function isNetworkTimeoutException(Exception $exception): bool
    {
        $message = strtolower($exception->getMessage());
        
        return str_contains($message, 'timeout') ||
               str_contains($message, 'connection timed out') ||
               str_contains($message, 'network') ||
               str_contains($message, 'curl error') ||
               $exception->getCode() === CURLE_OPERATION_TIMEOUTED ||
               (defined('CURLE_TIMEOUT') && $exception->getCode() === CURLE_TIMEOUT);
    }
}