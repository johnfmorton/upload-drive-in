<?php

namespace App\Enums;

/**
 * Universal cloud storage error types
 * 
 * Categorizes errors that can occur across all cloud storage providers
 * to enable consistent error handling and user messaging
 */
enum CloudStorageErrorType: string
{
    /**
     * Authentication token has expired and needs refresh
     */
    case TOKEN_EXPIRED = 'token_expired';

    /**
     * Insufficient permissions to perform the requested operation
     */
    case INSUFFICIENT_PERMISSIONS = 'insufficient_permissions';

    /**
     * API quota or rate limit has been exceeded
     */
    case API_QUOTA_EXCEEDED = 'api_quota_exceeded';

    /**
     * Network connectivity issues preventing API calls
     */
    case NETWORK_ERROR = 'network_error';

    /**
     * Requested file or folder was not found
     */
    case FILE_NOT_FOUND = 'file_not_found';

    /**
     * Access denied to specific folder or resource
     */
    case FOLDER_ACCESS_DENIED = 'folder_access_denied';

    /**
     * Cloud storage quota exceeded (no space left)
     */
    case STORAGE_QUOTA_EXCEEDED = 'storage_quota_exceeded';

    /**
     * File type not allowed by the provider
     */
    case INVALID_FILE_TYPE = 'invalid_file_type';

    /**
     * File size exceeds provider limits
     */
    case FILE_TOO_LARGE = 'file_too_large';

    /**
     * Invalid or malformed file content
     */
    case INVALID_FILE_CONTENT = 'invalid_file_content';

    /**
     * Provider service is temporarily unavailable
     */
    case SERVICE_UNAVAILABLE = 'service_unavailable';

    /**
     * Authentication configuration is invalid
     */
    case INVALID_CREDENTIALS = 'invalid_credentials';

    /**
     * Operation timed out
     */
    case TIMEOUT = 'timeout';

    /**
     * Unknown or unclassified error
     */
    case UNKNOWN_ERROR = 'unknown_error';

    /**
     * Get a human-readable description of the error type
     *
     * @return string Description of the error type
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::TOKEN_EXPIRED => 'Authentication token has expired',
            self::INSUFFICIENT_PERMISSIONS => 'Insufficient permissions for the operation',
            self::API_QUOTA_EXCEEDED => 'API quota or rate limit exceeded',
            self::NETWORK_ERROR => 'Network connectivity issue',
            self::FILE_NOT_FOUND => 'File or folder not found',
            self::FOLDER_ACCESS_DENIED => 'Access denied to folder or resource',
            self::STORAGE_QUOTA_EXCEEDED => 'Cloud storage quota exceeded',
            self::INVALID_FILE_TYPE => 'File type not allowed',
            self::FILE_TOO_LARGE => 'File size exceeds limits',
            self::INVALID_FILE_CONTENT => 'Invalid or malformed file content',
            self::SERVICE_UNAVAILABLE => 'Service temporarily unavailable',
            self::INVALID_CREDENTIALS => 'Invalid authentication configuration',
            self::TIMEOUT => 'Operation timed out',
            self::UNKNOWN_ERROR => 'Unknown error occurred',
        };
    }

    /**
     * Check if this error type is recoverable through retry
     *
     * @return bool True if the error is potentially recoverable
     */
    public function isRecoverable(): bool
    {
        return match ($this) {
            self::NETWORK_ERROR,
            self::SERVICE_UNAVAILABLE,
            self::TIMEOUT,
            self::API_QUOTA_EXCEEDED => true,
            default => false,
        };
    }

    /**
     * Check if this error type requires user intervention
     *
     * @return bool True if user intervention is required
     */
    public function requiresUserIntervention(): bool
    {
        return match ($this) {
            self::TOKEN_EXPIRED,
            self::INSUFFICIENT_PERMISSIONS,
            self::STORAGE_QUOTA_EXCEEDED,
            self::INVALID_CREDENTIALS => true,
            default => false,
        };
    }

    /**
     * Get the severity level of this error type
     *
     * @return string Severity level (low, medium, high, critical)
     */
    public function getSeverity(): string
    {
        return match ($this) {
            self::TOKEN_EXPIRED,
            self::INSUFFICIENT_PERMISSIONS,
            self::INVALID_CREDENTIALS => 'high',
            self::STORAGE_QUOTA_EXCEEDED,
            self::API_QUOTA_EXCEEDED => 'medium',
            self::NETWORK_ERROR,
            self::SERVICE_UNAVAILABLE,
            self::TIMEOUT => 'low',
            self::FILE_NOT_FOUND,
            self::FOLDER_ACCESS_DENIED,
            self::INVALID_FILE_TYPE,
            self::FILE_TOO_LARGE,
            self::INVALID_FILE_CONTENT => 'medium',
            self::UNKNOWN_ERROR => 'high',
        };
    }
}