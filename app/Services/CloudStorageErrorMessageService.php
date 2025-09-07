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
                __('messages.cloud_storage_error_token_expired', ['provider' => $providerName]),
            
            CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED => 
                $this->getTokenRefreshRateLimitMessage($context),
            
            CloudStorageErrorType::INVALID_CREDENTIALS => 
                __('messages.cloud_storage_error_invalid_credentials', ['provider' => $providerName]),
            
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => 
                __('messages.cloud_storage_error_insufficient_permissions', ['provider' => $providerName]),
            
            CloudStorageErrorType::API_QUOTA_EXCEEDED => 
                __('messages.cloud_storage_error_api_quota_exceeded', ['provider' => $providerName]),
            
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED => 
                __('messages.cloud_storage_error_storage_quota_exceeded', ['provider' => $providerName]),
            
            CloudStorageErrorType::NETWORK_ERROR => 
                __('messages.cloud_storage_error_network_error', ['provider' => $providerName]),
            
            CloudStorageErrorType::SERVICE_UNAVAILABLE => 
                __('messages.cloud_storage_error_service_unavailable', ['provider' => $providerName]),
            
            CloudStorageErrorType::TIMEOUT => 
                __('messages.cloud_storage_error_timeout', ['provider' => $providerName, 'operation' => $operation]),
            
            CloudStorageErrorType::FILE_NOT_FOUND => 
                __('messages.cloud_storage_error_file_not_found', ['filename' => $fileName, 'provider' => $providerName]),
            
            CloudStorageErrorType::FOLDER_ACCESS_DENIED => 
                __('messages.cloud_storage_error_folder_access_denied', ['provider' => $providerName]),
            
            CloudStorageErrorType::INVALID_FILE_TYPE => 
                __('messages.cloud_storage_error_invalid_file_type', ['filename' => $fileName, 'provider' => $providerName]),
            
            CloudStorageErrorType::FILE_TOO_LARGE => 
                __('messages.cloud_storage_error_file_too_large', ['filename' => $fileName, 'provider' => $providerName]),
            
            CloudStorageErrorType::INVALID_FILE_CONTENT => 
                __('messages.cloud_storage_error_invalid_file_content', ['filename' => $fileName]),
            
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED => 
                __('messages.cloud_storage_error_provider_not_configured', ['provider' => $providerName]),
            
            CloudStorageErrorType::UNKNOWN_ERROR => 
                __('messages.cloud_storage_error_unknown_error', [
                    'provider' => $providerName,
                    'message' => $context['original_message'] ?? __('messages.cloud_storage_recovery_default_3')
                ]),
            
            default => 
                __('messages.cloud_storage_error_default', ['provider' => $providerName, 'operation' => $operation])
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
                __('messages.cloud_storage_recovery_token_expired_1'),
                __('messages.cloud_storage_recovery_token_expired_2', ['provider' => $providerName]),
                __('messages.cloud_storage_recovery_token_expired_3'),
                __('messages.cloud_storage_recovery_token_expired_4')
            ],
            
            CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED => [
                __('messages.cloud_storage_recovery_rate_limited_1'),
                __('messages.cloud_storage_recovery_rate_limited_2'),
                __('messages.cloud_storage_recovery_rate_limited_3'),
                __('messages.cloud_storage_recovery_rate_limited_4')
            ],
            
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => [
                __('messages.cloud_storage_recovery_insufficient_permissions_1'),
                __('messages.cloud_storage_recovery_insufficient_permissions_2', ['provider' => $providerName]),
                __('messages.cloud_storage_recovery_insufficient_permissions_3'),
                __('messages.cloud_storage_recovery_insufficient_permissions_4')
            ],
            
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED => [
                __('messages.cloud_storage_recovery_storage_quota_exceeded_1', ['provider' => $providerName]),
                __('messages.cloud_storage_recovery_storage_quota_exceeded_2', ['provider' => $providerName]),
                __('messages.cloud_storage_recovery_storage_quota_exceeded_3', ['provider' => $providerName]),
                __('messages.cloud_storage_recovery_storage_quota_exceeded_4')
            ],
            
            CloudStorageErrorType::API_QUOTA_EXCEEDED => [
                __('messages.cloud_storage_recovery_api_quota_exceeded_1'),
                __('messages.cloud_storage_recovery_api_quota_exceeded_2'),
                __('messages.cloud_storage_recovery_api_quota_exceeded_3')
            ],
            
            CloudStorageErrorType::NETWORK_ERROR => [
                __('messages.cloud_storage_recovery_network_error_1'),
                __('messages.cloud_storage_recovery_network_error_2'),
                __('messages.cloud_storage_recovery_network_error_3')
            ],
            
            CloudStorageErrorType::SERVICE_UNAVAILABLE => [
                __('messages.cloud_storage_recovery_service_unavailable_1'),
                __('messages.cloud_storage_recovery_service_unavailable_2', ['provider' => $providerName]),
                __('messages.cloud_storage_recovery_service_unavailable_3')
            ],
            
            CloudStorageErrorType::TIMEOUT => [
                __('messages.cloud_storage_recovery_timeout_1'),
                __('messages.cloud_storage_recovery_timeout_2'),
                __('messages.cloud_storage_recovery_timeout_3')
            ],
            
            CloudStorageErrorType::FOLDER_ACCESS_DENIED => [
                __('messages.cloud_storage_recovery_folder_access_denied_1', ['provider' => $providerName]),
                __('messages.cloud_storage_recovery_folder_access_denied_2'),
                __('messages.cloud_storage_recovery_folder_access_denied_3', ['provider' => $providerName])
            ],
            
            CloudStorageErrorType::INVALID_FILE_TYPE => [
                __('messages.cloud_storage_recovery_invalid_file_type_1'),
                __('messages.cloud_storage_recovery_invalid_file_type_2', ['provider' => $providerName]),
                __('messages.cloud_storage_recovery_invalid_file_type_3')
            ],
            
            CloudStorageErrorType::FILE_TOO_LARGE => [
                __('messages.cloud_storage_recovery_file_too_large_1'),
                __('messages.cloud_storage_recovery_file_too_large_2'),
                __('messages.cloud_storage_recovery_file_too_large_3', ['provider' => $providerName])
            ],
            
            CloudStorageErrorType::INVALID_FILE_CONTENT => [
                __('messages.cloud_storage_recovery_invalid_file_content_1'),
                __('messages.cloud_storage_recovery_invalid_file_content_2'),
                __('messages.cloud_storage_recovery_invalid_file_content_3')
            ],
            
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED => [
                __('messages.cloud_storage_recovery_provider_not_configured_1'),
                __('messages.cloud_storage_recovery_provider_not_configured_2'),
                __('messages.cloud_storage_recovery_provider_not_configured_3'),
                __('messages.cloud_storage_recovery_provider_not_configured_4')
            ],
            
            CloudStorageErrorType::UNKNOWN_ERROR => [
                __('messages.cloud_storage_recovery_unknown_error_1'),
                __('messages.cloud_storage_recovery_unknown_error_2'),
                __('messages.cloud_storage_recovery_unknown_error_3'),
                __('messages.cloud_storage_recovery_unknown_error_4')
            ],
            
            default => [
                __('messages.cloud_storage_recovery_default_1'),
                __('messages.cloud_storage_recovery_default_2'),
                __('messages.cloud_storage_recovery_default_3')
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
     * Generate status display message based on consolidated status and error context
     *
     * @param string $consolidatedStatus The consolidated status from health service
     * @param array $context Additional error context
     * @return string User-friendly status message
     */
    public function getStatusDisplayMessage(string $consolidatedStatus, array $context = []): string
    {
        // Handle specific error contexts first (priority-based resolution)
        if (isset($context['error_type'])) {
            $errorType = is_string($context['error_type']) 
                ? CloudStorageErrorType::from($context['error_type'])
                : $context['error_type'];
            
            return $this->getActionableErrorMessage($errorType, $context);
        }
        
        // Handle consolidated status
        return match ($consolidatedStatus) {
            'healthy' => __('messages.' . CloudStorageStatusMessages::CONNECTION_HEALTHY_MESSAGE),
            'authentication_required' => __('messages.' . CloudStorageStatusMessages::AUTH_REQUIRED_MESSAGE),
            'connection_issues' => $this->getConnectionIssueMessage($context),
            'not_connected' => __('messages.' . CloudStorageStatusMessages::NOT_CONNECTED_MESSAGE),
            default => __('messages.' . CloudStorageStatusMessages::STATUS_UNKNOWN_MESSAGE)
        };
    }

    /**
     * Generate context-aware connection issue message
     *
     * @param array $context Error context including consecutive failures and error details
     * @return string Specific connection issue message
     */
    private function getConnectionIssueMessage(array $context): string
    {
        $consecutiveFailures = $context['consecutive_failures'] ?? 0;
        $errorType = $context['error_type'] ?? null;
        
        // Handle specific connection issues with priority
        if ($errorType === 'token_refresh_rate_limited' || $errorType === CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED) {
            return $this->getTokenRefreshRateLimitMessage($context);
        }
        
        return __('messages.' . CloudStorageStatusMessages::getConsecutiveFailureMessage($consecutiveFailures));
    }

    /**
     * Generate token refresh rate limit message with retry time if available
     *
     * @param array $context Context including retry_after time
     * @return string Rate limit message with timing information
     */
    private function getTokenRefreshRateLimitMessage(array $context): string
    {
        $retryAfter = $context['retry_after'] ?? null;
        
        if ($retryAfter && $retryAfter > 0) {
            return CloudStorageStatusMessages::getRetryTimeMessage($retryAfter);
        }
        
        return __('messages.' . CloudStorageStatusMessages::RATE_LIMITED_MESSAGE);
    }

    /**
     * Resolve message priority when multiple errors exist
     *
     * @param array $errorContexts Array of error contexts
     * @return string The highest priority message
     */
    public function resolveMessagePriority(array $errorContexts): string
    {
        // Priority order: Rate limiting > Auth required > Connection issues > Generic
        foreach ($errorContexts as $context) {
            if (isset($context['error_type']) && 
                ($context['error_type'] === 'token_refresh_rate_limited' || 
                 $context['error_type'] === CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED)) {
                return $this->getTokenRefreshRateLimitMessage($context);
            }
        }
        
        foreach ($errorContexts as $context) {
            if (isset($context['consolidated_status']) && $context['consolidated_status'] === 'authentication_required') {
                return __('messages.' . CloudStorageStatusMessages::AUTH_REQUIRED_MESSAGE);
            }
        }
        
        foreach ($errorContexts as $context) {
            if (isset($context['consolidated_status']) && $context['consolidated_status'] === 'connection_issues') {
                return $this->getConnectionIssueMessage($context);
            }
        }
        
        // Fallback to first available message
        if (!empty($errorContexts)) {
            $firstContext = reset($errorContexts);
            return $this->getStatusDisplayMessage($firstContext['consolidated_status'] ?? 'unknown', $firstContext);
        }
        
        return __('messages.' . CloudStorageStatusMessages::STATUS_UNKNOWN_MESSAGE);
    }

    /**
     * Get user-friendly provider display name
     *
     * @param string $provider The provider identifier
     * @return string User-friendly provider name
     */
    private function getProviderDisplayName(string $provider): string
    {
        $translationKey = match ($provider) {
            'google-drive' => 'cloud_storage_provider_google_drive',
            'amazon-s3' => 'cloud_storage_provider_amazon_s3',
            'azure-blob' => 'cloud_storage_provider_azure_blob',
            'microsoft-teams' => 'cloud_storage_provider_microsoft_teams',
            'dropbox' => 'cloud_storage_provider_dropbox',
            'onedrive' => 'cloud_storage_provider_onedrive',
            default => null
        };

        if ($translationKey && __('messages.' . $translationKey) !== 'messages.' . $translationKey) {
            return __('messages.' . $translationKey);
        }

        return ucfirst(str_replace('-', ' ', $provider));
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
            CloudStorageErrorType::API_QUOTA_EXCEEDED,
            CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED => true,
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
            CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED,
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