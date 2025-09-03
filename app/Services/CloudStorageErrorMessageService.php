<?php

namespace App\Services;

use App\Enums\CloudStorageErrorType;

/**
 * Service for generating actionable error messages for cloud storage operations
 * 
 * This service provides user-friendly error messages with recovery instructions
 * and recommended actions for different types of cloud storage failures.
 */
class CloudStorageErrorMessageService
{
    /**
     * Generate actionable error message for a given error type
     *
     * @param CloudStorageErrorType $errorType The type of error that occurred
     * @param array $context Additional context for message generation
     * @return string User-friendly error message
     */
    public function getActionableErrorMessage(CloudStorageErrorType $errorType, array $context = []): string
    {
        $provider = $context['provider'] ?? 'cloud storage';
        $providerName = $this->getProviderDisplayName($provider);
        $fileName = $context['file_name'] ?? 'file';
        $operation = $context['operation'] ?? 'operation';

        return match ($errorType) {
            CloudStorageErrorType::TOKEN_EXPIRED => 
                "Your {$providerName} connection has expired. Please reconnect your account to continue.",
            
            CloudStorageErrorType::INVALID_CREDENTIALS => 
                "Invalid {$providerName} credentials. Please check your configuration and reconnect your account.",
            
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => 
                "Insufficient {$providerName} permissions. Please reconnect your account and ensure you grant full access.",
            
            CloudStorageErrorType::API_QUOTA_EXCEEDED => 
                "{$providerName} API limit reached. Your operations will resume automatically when the limit resets.",
            
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED => 
                "Your {$providerName} storage is full. Please free up space or upgrade your storage plan.",
            
            CloudStorageErrorType::NETWORK_ERROR => 
                "Network connection issue prevented the {$providerName} operation. Please check your internet connection and try again.",
            
            CloudStorageErrorType::SERVICE_UNAVAILABLE => 
                "{$providerName} is temporarily unavailable. Please try again in a few minutes.",
            
            CloudStorageErrorType::TIMEOUT => 
                "The {$providerName} {$operation} timed out. This is usually temporary - please try again.",
            
            CloudStorageErrorType::FILE_NOT_FOUND => 
                "The file '{$fileName}' could not be found in {$providerName}. It may have been deleted or moved.",
            
            CloudStorageErrorType::FOLDER_ACCESS_DENIED => 
                "Access denied to the {$providerName} folder. Please check your folder permissions.",
            
            CloudStorageErrorType::INVALID_FILE_TYPE => 
                "The file type of '{$fileName}' is not supported by {$providerName}. Please try a different file format.",
            
            CloudStorageErrorType::FILE_TOO_LARGE => 
                "The file '{$fileName}' is too large for {$providerName}. Please reduce the file size and try again.",
            
            CloudStorageErrorType::INVALID_FILE_CONTENT => 
                "The file '{$fileName}' appears to be corrupted. Please try uploading the file again.",
            
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED => 
                "{$providerName} is not properly configured. Please check your settings and try again.",
            
            CloudStorageErrorType::UNKNOWN_ERROR => 
                "An unexpected error occurred with {$providerName}. " . 
                ($context['original_message'] ?? 'Please try again or contact support if the problem persists.'),
            
            default => 
                "An error occurred during the {$providerName} {$operation}. Please try again."
        };
    }

    /**
     * Get recovery instructions for a given error type
     *
     * @param CloudStorageErrorType $errorType The type of error that occurred
     * @param array $context Additional context for instructions
     * @return array List of recovery instructions
     */
    public function getRecoveryInstructions(CloudStorageErrorType $errorType, array $context = []): array
    {
        $provider = $context['provider'] ?? 'cloud storage';
        $providerName = $this->getProviderDisplayName($provider);

        return match ($errorType) {
            CloudStorageErrorType::TOKEN_EXPIRED, 
            CloudStorageErrorType::INVALID_CREDENTIALS => [
                'Go to Settings → Cloud Storage',
                "Click \"Reconnect {$providerName}\"",
                'Complete the authorization process',
                'Retry your operation'
            ],
            
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => [
                'Go to Settings → Cloud Storage',
                "Click \"Reconnect {$providerName}\"",
                'Ensure you grant full access when prompted',
                'Check that you have the necessary permissions'
            ],
            
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED => [
                "Free up space in your {$providerName} account",
                "Empty your {$providerName} trash",
                "Consider upgrading your {$providerName} storage plan",
                'Contact your administrator if using a business account'
            ],
            
            CloudStorageErrorType::API_QUOTA_EXCEEDED => [
                'Wait for the quota to reset (usually within an hour)',
                'Operations will resume automatically',
                'Consider spreading large operations across multiple days'
            ],
            
            CloudStorageErrorType::NETWORK_ERROR => [
                'Check your internet connection',
                'Try again in a few minutes',
                'Contact your network administrator if the problem persists'
            ],
            
            CloudStorageErrorType::SERVICE_UNAVAILABLE => [
                'Wait a few minutes and try again',
                "Check {$providerName} status page for service updates",
                'Operations will be retried automatically'
            ],
            
            CloudStorageErrorType::TIMEOUT => [
                'Try again - timeouts are usually temporary',
                'Check your internet connection speed',
                'For large files, try uploading during off-peak hours'
            ],
            
            CloudStorageErrorType::FOLDER_ACCESS_DENIED => [
                "Check that the target folder exists in your {$providerName}",
                'Verify you have write permissions to the folder',
                "Try reconnecting your {$providerName} account"
            ],
            
            CloudStorageErrorType::INVALID_FILE_TYPE => [
                'Convert the file to a supported format',
                "Check {$providerName}'s supported file types",
                'Try uploading a different file to test'
            ],
            
            CloudStorageErrorType::FILE_TOO_LARGE => [
                'Compress the file to reduce its size',
                'Split large files into smaller parts',
                "Use {$providerName}'s web interface for very large files"
            ],
            
            CloudStorageErrorType::INVALID_FILE_CONTENT => [
                'Check that the file is not corrupted',
                'Try re-creating or re-downloading the file',
                'Scan the file for viruses or malware'
            ],
            
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED => [
                'Go to Settings → Cloud Storage',
                'Check your configuration settings',
                'Ensure all required fields are filled correctly',
                'Contact support if you need assistance'
            ],
            
            CloudStorageErrorType::UNKNOWN_ERROR => [
                'Try the operation again',
                'Check your internet connection',
                'Contact support if the problem persists',
                'Include any error details when contacting support'
            ],
            
            default => [
                'Try the operation again',
                'Check your connection and settings',
                'Contact support if the problem persists'
            ]
        };
    }

    /**
     * Determine if technical details should be shown to the user
     *
     * @param mixed $user The user object (should have isAdmin method)
     * @return bool Whether to show technical details
     */
    public function shouldShowTechnicalDetails($user): bool
    {
        if (!$user) {
            return false;
        }

        // Show technical details to admin users
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        // Show technical details to users with admin role
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        // Check for role property
        if (property_exists($user, 'role') && $user->role === 'admin') {
            return true;
        }

        return false;
    }

    /**
     * Get user-friendly provider display name
     *
     * @param string $provider The provider identifier
     * @return string User-friendly provider name
     */
    private function getProviderDisplayName(string $provider): string
    {
        return match ($provider) {
            'google-drive' => 'Google Drive',
            'amazon-s3' => 'Amazon S3',
            'azure-blob' => 'Azure Blob Storage',
            'microsoft-teams' => 'Microsoft Teams',
            'dropbox' => 'Dropbox',
            'onedrive' => 'OneDrive',
            default => ucfirst(str_replace('-', ' ', $provider))
        };
    }

    /**
     * Generate a comprehensive error response with message and instructions
     *
     * @param CloudStorageErrorType $errorType The type of error that occurred
     * @param array $context Additional context for message generation
     * @return array Comprehensive error response
     */
    public function generateErrorResponse(CloudStorageErrorType $errorType, array $context = []): array
    {
        $message = $this->getActionableErrorMessage($errorType, $context);
        $instructions = $this->getRecoveryInstructions($errorType, $context);
        $showTechnical = $this->shouldShowTechnicalDetails($context['user'] ?? null);

        $response = [
            'error_type' => $errorType->value,
            'message' => $message,
            'instructions' => $instructions,
            'is_retryable' => $this->isRetryableError($errorType),
            'requires_user_action' => $this->requiresUserAction($errorType),
        ];

        if ($showTechnical && isset($context['technical_details'])) {
            $response['technical_details'] = $context['technical_details'];
        }

        if (isset($context['retry_after'])) {
            $response['retry_after'] = $context['retry_after'];
        }

        return $response;
    }

    /**
     * Determine if an error type is retryable
     *
     * @param CloudStorageErrorType $errorType The error type
     * @return bool Whether the error is retryable
     */
    private function isRetryableError(CloudStorageErrorType $errorType): bool
    {
        return match ($errorType) {
            CloudStorageErrorType::NETWORK_ERROR,
            CloudStorageErrorType::SERVICE_UNAVAILABLE,
            CloudStorageErrorType::TIMEOUT,
            CloudStorageErrorType::API_QUOTA_EXCEEDED => true,
            default => false
        };
    }

    /**
     * Determine if an error type requires user action
     *
     * @param CloudStorageErrorType $errorType The error type
     * @return bool Whether the error requires user action
     */
    private function requiresUserAction(CloudStorageErrorType $errorType): bool
    {
        return match ($errorType) {
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::INVALID_CREDENTIALS,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            CloudStorageErrorType::FOLDER_ACCESS_DENIED,
            CloudStorageErrorType::INVALID_FILE_TYPE,
            CloudStorageErrorType::FILE_TOO_LARGE,
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED => true,
            default => false
        };
    }
}