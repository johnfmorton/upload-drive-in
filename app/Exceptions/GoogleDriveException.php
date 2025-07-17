<?php

namespace App\Exceptions;

use Google\Service\Exception as GoogleServiceException;

class GoogleDriveException extends FileManagerException
{
    public static function apiError(GoogleServiceException $exception, string $operation = 'operation'): self
    {
        $errors = $exception->getErrors();
        $reason = $errors[0]['reason'] ?? 'unknown';
        $domain = $errors[0]['domain'] ?? 'unknown';

        return new self(
            message: "Google Drive API error during {$operation}: {$exception->getMessage()}",
            userMessage: self::getUserFriendlyMessage($reason, $operation),
            code: $exception->getCode(),
            previous: $exception,
            context: [
                'operation' => $operation,
                'reason' => $reason,
                'domain' => $domain,
                'errors' => $errors,
                'type' => 'google_drive_api_error'
            ],
            isRetryable: self::isRetryableError($reason)
        );
    }

    public static function tokenExpired(string $userId): self
    {
        return new self(
            message: "Google Drive token expired for user {$userId}",
            userMessage: "Your Google Drive access has expired. Please reconnect your Google Drive account in the settings.",
            code: 401,
            context: [
                'user_id' => $userId,
                'type' => 'token_expired'
            ]
        );
    }

    public static function quotaExceeded(string $operation): self
    {
        return new self(
            message: "Google Drive quota exceeded during {$operation}",
            userMessage: "Google Drive usage limit has been reached. Please try again later or contact support.",
            code: 429,
            context: [
                'operation' => $operation,
                'type' => 'quota_exceeded'
            ],
            isRetryable: true
        );
    }

    public static function fileNotFoundInDrive(string $fileId, string $filename = null): self
    {
        return new self(
            message: "File not found in Google Drive: {$fileId}" . ($filename ? " ({$filename})" : ''),
            userMessage: "The file could not be found in Google Drive. It may have been deleted or moved.",
            code: 404,
            context: [
                'google_drive_file_id' => $fileId,
                'filename' => $filename,
                'type' => 'file_not_found_in_drive'
            ]
        );
    }

    public static function uploadFailed(string $filename, string $reason = null): self
    {
        return new self(
            message: "Failed to upload file to Google Drive: {$filename}" . ($reason ? " - {$reason}" : ''),
            userMessage: "Failed to upload '{$filename}' to Google Drive. Please try again.",
            code: 500,
            context: [
                'filename' => $filename,
                'reason' => $reason,
                'type' => 'upload_failed'
            ],
            isRetryable: true
        );
    }

    /**
     * Get user-friendly message based on Google API error reason.
     */
    private static function getUserFriendlyMessage(string $reason, string $operation): string
    {
        return match ($reason) {
            'notFound' => 'The requested file could not be found in Google Drive.',
            'forbidden' => 'Access to Google Drive is currently restricted. Please check your permissions.',
            'quotaExceeded', 'rateLimitExceeded' => 'Google Drive usage limit reached. Please try again later.',
            'authError', 'unauthorized' => 'Google Drive authentication failed. Please reconnect your account.',
            'backendError', 'internalError' => 'Google Drive is temporarily unavailable. Please try again later.',
            'badRequest' => 'Invalid request to Google Drive. Please contact support if this persists.',
            default => "Google Drive error during {$operation}. Please try again or contact support.",
        };
    }

    /**
     * Check if the error is retryable based on the reason.
     */
    private static function isRetryableError(string $reason): bool
    {
        return in_array($reason, [
            'quotaExceeded',
            'rateLimitExceeded',
            'backendError',
            'internalError',
            'serviceUnavailable'
        ]);
    }
}