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
                __('messages.cloud_storage_token_expired', ['provider' => $providerName]),
            
            CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED => 
                $this->getTokenRefreshRateLimitMessage($context),
            
            CloudStorageErrorType::INVALID_CREDENTIALS => 
                __('messages.cloud_storage_invalid_credentials', ['provider' => $providerName]),
            
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => 
                __('messages.cloud_storage_insufficient_permissions', ['provider' => $providerName]),
            
            CloudStorageErrorType::API_QUOTA_EXCEEDED => 
                __('messages.cloud_storage_api_quota_exceeded', ['provider' => $providerName]),
            
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED => 
                __('messages.cloud_storage_storage_quota_exceeded', ['provider' => $providerName]),
            
            CloudStorageErrorType::NETWORK_ERROR => 
                __('messages.cloud_storage_network_error', ['provider' => $providerName]),
            
            CloudStorageErrorType::SERVICE_UNAVAILABLE => 
                __('messages.cloud_storage_service_unavailable', ['provider' => $providerName]),
            
            CloudStorageErrorType::TIMEOUT => 
                __('messages.cloud_storage_timeout', ['provider' => $providerName, 'operation' => $operation]),
            
            CloudStorageErrorType::FILE_NOT_FOUND => 
                __('messages.cloud_storage_file_not_found', ['filename' => $fileName, 'provider' => $providerName]),
            
            CloudStorageErrorType::FOLDER_ACCESS_DENIED => 
                __('messages.cloud_storage_folder_access_denied', ['provider' => $providerName]),
            
            CloudStorageErrorType::INVALID_FILE_TYPE => 
                __('messages.cloud_storage_invalid_file_type', ['filename' => $fileName, 'provider' => $providerName]),
            
            CloudStorageErrorType::FILE_TOO_LARGE => 
                __('messages.cloud_storage_file_too_large', ['filename' => $fileName, 'provider' => $providerName]),
            
            CloudStorageErrorType::INVALID_FILE_CONTENT => 
                __('messages.cloud_storage_invalid_file_content', ['filename' => $fileName]),
            
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED => 
                __('messages.cloud_storage_provider_not_configured', ['provider' => $providerName]),
            
            CloudStorageErrorType::UNKNOWN_ERROR => 
                __('messages.cloud_storage_unknown_error', [
                    'provider' => $providerName,
                    'message' => $context['original_message'] ?? __('messages.cloud_storage_default_error', ['provider' => $providerName, 'operation' => $operation])
                ]),
            
            default => 
                __('messages.cloud_storage_default_error', ['provider' => $providerName, 'operation' => $operation])
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
            
            CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED => [
                'Wait for the rate limit to reset',
                'Avoid repeatedly clicking connection test buttons',
                'Operations will resume automatically when the limit resets',
                'Contact support if the issue persists beyond the expected time'
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
            'healthy' => __('messages.cloud_storage_connection_healthy'),
            'authentication_required' => __('messages.cloud_storage_auth_required'),
            'connection_issues' => $this->getConnectionIssueMessage($context),
            'not_connected' => __('messages.cloud_storage_not_connected'),
            default => __('messages.cloud_storage_status_unknown')
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
        $lastErrorMessage = $context['last_error_message'] ?? null;
        $provider = $context['provider'] ?? 'cloud storage';
        
        // Handle specific connection issues with priority-based resolution
        if ($errorType === 'token_refresh_rate_limited' || $errorType === CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED) {
            return $this->getTokenRefreshRateLimitMessage($context);
        }
        
        // Handle authentication-related connection issues
        if ($errorType === 'token_expired' || $errorType === CloudStorageErrorType::TOKEN_EXPIRED) {
            return __('messages.cloud_storage_token_expired', ['provider' => $this->getProviderDisplayName($provider)]);
        }
        
        if ($errorType === 'invalid_credentials' || $errorType === CloudStorageErrorType::INVALID_CREDENTIALS) {
            return __('messages.cloud_storage_invalid_credentials', ['provider' => $this->getProviderDisplayName($provider)]);
        }
        
        if ($errorType === 'insufficient_permissions' || $errorType === CloudStorageErrorType::INSUFFICIENT_PERMISSIONS) {
            return __('messages.cloud_storage_insufficient_permissions', ['provider' => $this->getProviderDisplayName($provider)]);
        }
        
        // Handle quota and storage issues
        if ($errorType === 'storage_quota_exceeded' || $errorType === CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED) {
            return __('messages.cloud_storage_storage_quota_exceeded', ['provider' => $this->getProviderDisplayName($provider)]);
        }
        
        if ($errorType === 'api_quota_exceeded' || $errorType === CloudStorageErrorType::API_QUOTA_EXCEEDED) {
            return __('messages.cloud_storage_api_quota_exceeded', ['provider' => $this->getProviderDisplayName($provider)]);
        }
        
        // Handle network and service issues
        if ($errorType === 'network_error' || $errorType === CloudStorageErrorType::NETWORK_ERROR) {
            return __('messages.cloud_storage_network_error', ['provider' => $this->getProviderDisplayName($provider)]);
        }
        
        if ($errorType === 'service_unavailable' || $errorType === CloudStorageErrorType::SERVICE_UNAVAILABLE) {
            return __('messages.cloud_storage_service_unavailable', ['provider' => $this->getProviderDisplayName($provider)]);
        }
        
        if ($errorType === 'timeout' || $errorType === CloudStorageErrorType::TIMEOUT) {
            return __('messages.cloud_storage_timeout', ['provider' => $this->getProviderDisplayName($provider), 'operation' => 'operation']);
        }
        
        // Handle consecutive failures with context-aware messaging
        if ($consecutiveFailures > 5) {
            return __('messages.cloud_storage_persistent_failures', ['provider' => $this->getProviderDisplayName($provider)]);
        }
        
        if ($consecutiveFailures > 3) {
            return __(CloudStorageStatusMessages::MULTIPLE_FAILURES_MESSAGE);
        }
        
        if ($consecutiveFailures > 1) {
            return __(CloudStorageStatusMessages::CONNECTION_ISSUES_MESSAGE);
        }
        
        // Fallback based on available context
        if ($lastErrorMessage && !empty(trim($lastErrorMessage))) {
            // Avoid showing technical error messages to users
            if (!$this->isTechnicalErrorMessage($lastErrorMessage)) {
                return $lastErrorMessage;
            }
        }
        
        return __(CloudStorageStatusMessages::CONNECTION_ISSUES_MESSAGE);
    }

    /**
     * Generate token refresh rate limit message with retry time if available
     *
     * @param array $context Context including retry_after time, consecutive failures, and provider
     * @return string Rate limit message with timing information
     */
    private function getTokenRefreshRateLimitMessage(array $context): string
    {
        $retryAfter = $context['retry_after'] ?? null;
        $consecutiveFailures = $context['consecutive_failures'] ?? 0;
        $provider = $context['provider'] ?? null;
        $lastAttemptTime = $context['last_attempt_time'] ?? null;
        
        // If we have specific retry time, use it
        if ($retryAfter && $retryAfter > 0) {
            return CloudStorageStatusMessages::getRetryTimeMessage($retryAfter);
        }
        
        // If we have consecutive failures, provide more context
        if ($consecutiveFailures > 5) {
            $providerName = $provider ? $this->getProviderDisplayName($provider) : 'cloud storage';
            return __('messages.cloud_storage_token_refresh_rate_limited', ['provider' => $providerName]);
        }
        
        if ($consecutiveFailures > 3) {
            return __('messages.cloud_storage_multiple_token_refresh_attempts');
        }
        
        // If we have last attempt time, calculate approximate wait time
        if ($lastAttemptTime) {
            try {
                $lastAttempt = is_string($lastAttemptTime) ? new \DateTime($lastAttemptTime) : $lastAttemptTime;
                $now = new \DateTime();
                $timeSinceLastAttempt = $now->getTimestamp() - $lastAttempt->getTimestamp();
                
                // If less than 5 minutes since last attempt, suggest waiting
                if ($timeSinceLastAttempt < 300) {
                    $waitTime = 300 - $timeSinceLastAttempt;
                    $minutes = ceil($waitTime / 60);
                    return __('messages.cloud_storage_retry_with_time', ['minutes' => $minutes]);
                }
            } catch (\Exception $e) {
                // Fall through to default message if date parsing fails
            }
        }
        
        // Default rate limit message
        return __(CloudStorageStatusMessages::RATE_LIMITED_MESSAGE);
    }

    /**
     * Resolve message priority when multiple errors exist
     *
     * @param array $errorContexts Array of error contexts
     * @return string The highest priority message
     */
    public function resolveMessagePriority(array $errorContexts): string
    {
        if (empty($errorContexts)) {
            return __(CloudStorageStatusMessages::STATUS_UNKNOWN_MESSAGE);
        }
        
        // Priority levels (highest to lowest):
        // 1. Rate limiting (immediate user action blocker)
        // 2. Authentication issues (user action required)
        // 3. Storage/quota issues (user action required)
        // 4. Permission issues (user action required)
        // 5. Service/network issues (temporary, may resolve automatically)
        // 6. Generic connection issues
        // 7. Unknown/other issues
        
        $priorityOrder = [
            // Level 1: Rate limiting (highest priority)
            ['error_type' => ['token_refresh_rate_limited', CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED]],
            
            // Level 2: Authentication issues
            ['error_type' => ['token_expired', CloudStorageErrorType::TOKEN_EXPIRED]],
            ['error_type' => ['invalid_credentials', CloudStorageErrorType::INVALID_CREDENTIALS]],
            ['consolidated_status' => 'authentication_required'],
            
            // Level 3: Storage and quota issues
            ['error_type' => ['storage_quota_exceeded', CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED]],
            ['error_type' => ['api_quota_exceeded', CloudStorageErrorType::API_QUOTA_EXCEEDED]],
            
            // Level 4: Permission issues
            ['error_type' => ['insufficient_permissions', CloudStorageErrorType::INSUFFICIENT_PERMISSIONS]],
            ['error_type' => ['folder_access_denied', CloudStorageErrorType::FOLDER_ACCESS_DENIED]],
            
            // Level 5: Service and network issues (temporary)
            ['error_type' => ['service_unavailable', CloudStorageErrorType::SERVICE_UNAVAILABLE]],
            ['error_type' => ['network_error', CloudStorageErrorType::NETWORK_ERROR]],
            ['error_type' => ['timeout', CloudStorageErrorType::TIMEOUT]],
            
            // Level 6: Generic connection issues
            ['consolidated_status' => 'connection_issues'],
            
            // Level 7: Other statuses
            ['consolidated_status' => 'not_connected'],
        ];
        
        // Check each priority level
        foreach ($priorityOrder as $priority) {
            foreach ($errorContexts as $context) {
                if (isset($priority['error_type'])) {
                    $errorType = $context['error_type'] ?? null;
                    if (in_array($errorType, $priority['error_type'], true)) {
                        return $this->getContextualMessage($context);
                    }
                }
                
                if (isset($priority['consolidated_status'])) {
                    $status = $context['consolidated_status'] ?? null;
                    if ($status === $priority['consolidated_status']) {
                        return $this->getContextualMessage($context);
                    }
                }
            }
        }
        
        // Fallback to first available context
        $firstContext = reset($errorContexts);
        return $this->getContextualMessage($firstContext);
    }
    
    /**
     * Get contextual message based on error context
     *
     * @param array $context Error context
     * @return string Contextual message
     */
    private function getContextualMessage(array $context): string
    {
        $errorType = $context['error_type'] ?? null;
        $consolidatedStatus = $context['consolidated_status'] ?? 'unknown';
        
        // Handle specific error types first
        if ($errorType) {
            if (is_string($errorType)) {
                try {
                    $errorType = CloudStorageErrorType::from($errorType);
                } catch (\ValueError $e) {
                    // Handle string error types that don't match enum
                    return $this->getStatusDisplayMessage($consolidatedStatus, $context);
                }
            }
            
            if ($errorType instanceof CloudStorageErrorType) {
                return $this->getActionableErrorMessage($errorType, $context);
            }
        }
        
        // Fallback to status-based message
        return $this->getStatusDisplayMessage($consolidatedStatus, $context);
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
     * Generate context-aware message considering error types, consecutive failures, and timing
     *
     * @param array $context Full error context including error type, failures, timing, etc.
     * @return array Comprehensive message analysis
     */
    public function generateContextAwareMessage(array $context): array
    {
        $errorType = $context['error_type'] ?? null;
        $consolidatedStatus = $context['consolidated_status'] ?? 'unknown';
        $consecutiveFailures = $context['consecutive_failures'] ?? 0;
        $provider = $context['provider'] ?? 'cloud storage';
        $user = $context['user'] ?? null;
        
        // Determine the primary message using priority resolution
        $primaryMessage = $this->getContextualMessage($context);
        
        // Determine message urgency based on context
        $urgency = $this->determineMessageUrgency($context);
        
        // Get appropriate action buttons based on context
        $actionButtons = $this->getContextualActionButtons($context);
        
        // Determine if technical details should be shown
        $showTechnical = $this->shouldShowTechnicalDetails($user);
        
        return [
            'message' => $primaryMessage,
            'urgency' => $urgency,
            'action_buttons' => $actionButtons,
            'show_technical_details' => $showTechnical,
            'is_retryable' => $this->isContextRetryable($context),
            'requires_user_action' => $this->contextRequiresUserAction($context),
            'consecutive_failures' => $consecutiveFailures,
            'provider' => $provider,
            'message_type' => $this->getMessageType($context),
        ];
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

    /**
     * Check if an error message is technical and should not be shown to users
     *
     * @param string $errorMessage The error message to check
     * @return bool Whether the message is technical
     */
    private function isTechnicalErrorMessage(string $errorMessage): bool
    {
        $technicalPatterns = [
            '/HTTP \d{3}/',                    // HTTP status codes
            '/Exception/',                     // Exception class names
            '/Fatal error/',                   // PHP fatal errors
            '/Parse error/',                   // PHP parse errors
            '/Call to undefined/',             // PHP undefined function/method
            '/Class .* not found/',            // PHP class not found
            '/Undefined variable/',            // PHP undefined variable
            '/cURL error/',                    // cURL errors
            '/SSL certificate/',               // SSL certificate errors
            '/Connection refused/',            // Network connection errors
            '/Timeout was reached/',           // Network timeout errors
            '/Could not resolve host/',        // DNS resolution errors
            '/JSON decode error/',             // JSON parsing errors
            '/Invalid JSON/',                  // JSON validation errors
            '/Database connection failed/',    // Database errors
            '/Query failed/',                  // Database query errors
            '/Access denied for user/',        // Database access errors
            '/Table .* doesn\'t exist/',       // Database table errors
            '/Column .* not found/',           // Database column errors
            '/Duplicate entry/',               // Database constraint errors
            '/Foreign key constraint/',        // Database foreign key errors
            '/Disk full/',                     // Disk space errors
            '/Permission denied/',             // File permission errors (when technical)
            '/No such file or directory/',     // File system errors
            '/Memory limit exceeded/',         // PHP memory errors
            '/Maximum execution time/',        // PHP timeout errors
            '/Stack trace:/',                  // Stack traces
            '/in \/.*\.php on line \d+/',     // PHP file/line references
        ];
        
        foreach ($technicalPatterns as $pattern) {
            if (preg_match($pattern, $errorMessage)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Determine message urgency based on error context
     *
     * @param array $context Error context
     * @return string Urgency level (low, medium, high, critical)
     */
    private function determineMessageUrgency(array $context): string
    {
        $errorType = $context['error_type'] ?? null;
        $consecutiveFailures = $context['consecutive_failures'] ?? 0;
        
        // Critical urgency for blocking issues
        if ($errorType === 'token_refresh_rate_limited' || $errorType === CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED) {
            return 'critical';
        }
        
        if ($consecutiveFailures > 5) {
            return 'critical';
        }
        
        // High urgency for authentication and permission issues
        if (in_array($errorType, [
            'token_expired', CloudStorageErrorType::TOKEN_EXPIRED,
            'invalid_credentials', CloudStorageErrorType::INVALID_CREDENTIALS,
            'insufficient_permissions', CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
        ], true)) {
            return 'high';
        }
        
        // Medium urgency for quota and storage issues
        if (in_array($errorType, [
            'storage_quota_exceeded', CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            'api_quota_exceeded', CloudStorageErrorType::API_QUOTA_EXCEEDED,
        ], true)) {
            return 'medium';
        }
        
        // Low urgency for temporary issues
        if (in_array($errorType, [
            'network_error', CloudStorageErrorType::NETWORK_ERROR,
            'service_unavailable', CloudStorageErrorType::SERVICE_UNAVAILABLE,
            'timeout', CloudStorageErrorType::TIMEOUT,
        ], true)) {
            return 'low';
        }
        
        return 'medium';
    }

    /**
     * Get contextual action buttons based on error context
     *
     * @param array $context Error context
     * @return array Action buttons configuration
     */
    private function getContextualActionButtons(array $context): array
    {
        $errorType = $context['error_type'] ?? null;
        $consolidatedStatus = $context['consolidated_status'] ?? null;
        $retryAfter = $context['retry_after'] ?? null;
        
        $buttons = [];
        
        // Rate limited - show countdown or wait message
        if ($errorType === 'token_refresh_rate_limited' || $errorType === CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED) {
            if ($retryAfter && $retryAfter > 0) {
                $buttons[] = [
                    'type' => 'wait',
                    'label' => 'Please wait...',
                    'disabled' => true,
                    'countdown' => $retryAfter
                ];
            } else {
                $buttons[] = [
                    'type' => 'wait',
                    'label' => 'Please wait before retrying',
                    'disabled' => true
                ];
            }
            return $buttons;
        }
        
        // Authentication issues - show reconnect button
        if (in_array($errorType, [
            'token_expired', CloudStorageErrorType::TOKEN_EXPIRED,
            'invalid_credentials', CloudStorageErrorType::INVALID_CREDENTIALS,
            'insufficient_permissions', CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
        ], true) || $consolidatedStatus === 'authentication_required') {
            $buttons[] = [
                'type' => 'reconnect',
                'label' => 'Reconnect Account',
                'action' => 'reconnect',
                'primary' => true
            ];
        }
        
        // Connection issues - show test connection button
        if ($consolidatedStatus === 'connection_issues' && !in_array($errorType, [
            'token_refresh_rate_limited', CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED
        ], true)) {
            $buttons[] = [
                'type' => 'test',
                'label' => 'Test Connection',
                'action' => 'test_connection',
                'primary' => true
            ];
        }
        
        // Not connected - show setup button
        if ($consolidatedStatus === 'not_connected') {
            $buttons[] = [
                'type' => 'setup',
                'label' => 'Set Up Connection',
                'action' => 'setup',
                'primary' => true
            ];
        }
        
        // Always show refresh button for non-rate-limited scenarios
        if (!in_array($errorType, [
            'token_refresh_rate_limited', CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED
        ], true)) {
            $buttons[] = [
                'type' => 'refresh',
                'label' => 'Refresh Status',
                'action' => 'refresh',
                'primary' => false
            ];
        }
        
        return $buttons;
    }

    /**
     * Check if context indicates a retryable situation
     *
     * @param array $context Error context
     * @return bool Whether the situation is retryable
     */
    private function isContextRetryable(array $context): bool
    {
        $errorType = $context['error_type'] ?? null;
        $consecutiveFailures = $context['consecutive_failures'] ?? 0;
        
        // Not retryable if too many consecutive failures
        if ($consecutiveFailures > 10) {
            return false;
        }
        
        // Rate limited is retryable but with delay
        if ($errorType === 'token_refresh_rate_limited' || $errorType === CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED) {
            return true;
        }
        
        // Use enum method if available
        if ($errorType instanceof CloudStorageErrorType) {
            return $errorType->isRecoverable();
        }
        
        // Check string error types
        return in_array($errorType, [
            'network_error', 'service_unavailable', 'timeout', 'api_quota_exceeded'
        ], true);
    }

    /**
     * Check if context requires user action
     *
     * @param array $context Error context
     * @return bool Whether user action is required
     */
    private function contextRequiresUserAction(array $context): bool
    {
        $errorType = $context['error_type'] ?? null;
        $consolidatedStatus = $context['consolidated_status'] ?? null;
        
        // Authentication and permission issues require user action
        if (in_array($consolidatedStatus, ['authentication_required', 'not_connected'])) {
            return true;
        }
        
        // Use enum method if available
        if ($errorType instanceof CloudStorageErrorType) {
            return $errorType->requiresUserIntervention();
        }
        
        // Check string error types that require user action
        return in_array($errorType, [
            'token_expired', 'token_refresh_rate_limited', 'invalid_credentials',
            'insufficient_permissions', 'storage_quota_exceeded', 'provider_not_configured'
        ], true);
    }

    /**
     * Get message type for categorization
     *
     * @param array $context Error context
     * @return string Message type
     */
    private function getMessageType(array $context): string
    {
        $errorType = $context['error_type'] ?? null;
        $consolidatedStatus = $context['consolidated_status'] ?? null;
        
        if ($errorType === 'token_refresh_rate_limited' || $errorType === CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED) {
            return 'rate_limit';
        }
        
        if (in_array($errorType, [
            'token_expired', CloudStorageErrorType::TOKEN_EXPIRED,
            'invalid_credentials', CloudStorageErrorType::INVALID_CREDENTIALS,
            'insufficient_permissions', CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
        ], true) || $consolidatedStatus === 'authentication_required') {
            return 'authentication';
        }
        
        if (in_array($errorType, [
            'storage_quota_exceeded', CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            'api_quota_exceeded', CloudStorageErrorType::API_QUOTA_EXCEEDED,
        ], true)) {
            return 'quota';
        }
        
        if (in_array($errorType, [
            'network_error', CloudStorageErrorType::NETWORK_ERROR,
            'service_unavailable', CloudStorageErrorType::SERVICE_UNAVAILABLE,
            'timeout', CloudStorageErrorType::TIMEOUT,
        ], true)) {
            return 'temporary';
        }
        
        if ($consolidatedStatus === 'connection_issues') {
            return 'connection';
        }
        
        if ($consolidatedStatus === 'not_connected') {
            return 'setup';
        }
        
        return 'general';
    }
}