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
     * Token refresh rate limit exceeded - too many refresh attempts
     */
    case TOKEN_REFRESH_RATE_LIMITED = 'token_refresh_rate_limited';

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

    // ========================================
    // S3-SPECIFIC ERROR TYPES
    // ========================================

    /**
     * S3 bucket not found
     */
    case BUCKET_NOT_FOUND = 'bucket_not_found';

    /**
     * Invalid S3 bucket name
     */
    case INVALID_BUCKET_NAME = 'invalid_bucket_name';

    /**
     * S3 bucket access denied
     */
    case BUCKET_ACCESS_DENIED = 'bucket_access_denied';

    /**
     * Invalid AWS region
     */
    case INVALID_REGION = 'invalid_region';

    /**
     * S3 storage class not supported
     */
    case STORAGE_CLASS_NOT_SUPPORTED = 'storage_class_not_supported';

    // ========================================
    // GENERIC PROVIDER ERROR TYPES
    // ========================================

    /**
     * Provider not configured
     */
    case PROVIDER_NOT_CONFIGURED = 'provider_not_configured';

    /**
     * Provider initialization failed
     */
    case PROVIDER_INITIALIZATION_FAILED = 'provider_initialization_failed';

    /**
     * Feature not supported by provider
     */
    case FEATURE_NOT_SUPPORTED = 'feature_not_supported';

    /**
     * Invalid parameter provided
     */
    case INVALID_PARAMETER = 'invalid_parameter';

    /**
     * API error occurred during operation
     */
    case API_ERROR = 'api_error';

    /**
     * Operation not supported by provider
     */
    case UNSUPPORTED_OPERATION = 'unsupported_operation';

    /**
     * Get a human-readable description of the error type
     *
     * @return string Description of the error type
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::TOKEN_EXPIRED => 'Authentication token has expired',
            self::TOKEN_REFRESH_RATE_LIMITED => 'Token refresh rate limit exceeded',
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
            self::BUCKET_NOT_FOUND => 'S3 bucket not found',
            self::INVALID_BUCKET_NAME => 'Invalid S3 bucket name',
            self::BUCKET_ACCESS_DENIED => 'S3 bucket access denied',
            self::INVALID_REGION => 'Invalid AWS region',
            self::STORAGE_CLASS_NOT_SUPPORTED => 'S3 storage class not supported',
            self::PROVIDER_NOT_CONFIGURED => 'Provider not configured',
            self::PROVIDER_INITIALIZATION_FAILED => 'Provider initialization failed',
            self::FEATURE_NOT_SUPPORTED => 'Feature not supported by provider',
            self::INVALID_PARAMETER => 'Invalid parameter provided',
            self::API_ERROR => 'API error occurred during operation',
            self::UNSUPPORTED_OPERATION => 'Operation not supported by provider',
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
            self::API_QUOTA_EXCEEDED,
            self::API_ERROR => true,
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
            self::TOKEN_REFRESH_RATE_LIMITED,
            self::INSUFFICIENT_PERMISSIONS,
            self::STORAGE_QUOTA_EXCEEDED,
            self::INVALID_CREDENTIALS,
            self::BUCKET_NOT_FOUND,
            self::INVALID_BUCKET_NAME,
            self::BUCKET_ACCESS_DENIED,
            self::INVALID_REGION,
            self::PROVIDER_NOT_CONFIGURED,
            self::PROVIDER_INITIALIZATION_FAILED,
            self::INVALID_PARAMETER,
            self::UNSUPPORTED_OPERATION => true,
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
            self::TOKEN_REFRESH_RATE_LIMITED,
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
            self::INVALID_FILE_CONTENT,
            self::STORAGE_CLASS_NOT_SUPPORTED,
            self::FEATURE_NOT_SUPPORTED,
            self::INVALID_PARAMETER,
            self::API_ERROR,
            self::UNSUPPORTED_OPERATION => 'medium',
            self::BUCKET_NOT_FOUND,
            self::INVALID_BUCKET_NAME,
            self::BUCKET_ACCESS_DENIED,
            self::INVALID_REGION,
            self::PROVIDER_NOT_CONFIGURED,
            self::PROVIDER_INITIALIZATION_FAILED,
            self::UNKNOWN_ERROR => 'high',
        };
    }
}