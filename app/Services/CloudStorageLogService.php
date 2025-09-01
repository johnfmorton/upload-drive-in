<?php

namespace App\Services;

use App\Enums\CloudStorageErrorType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Cloud Storage Logging Service
 * 
 * Provides structured logging for all cloud storage operations with
 * comprehensive context, error classification, and performance metrics.
 */
class CloudStorageLogService
{
    private const LOG_CHANNEL = 'cloud-storage';
    private const AUDIT_CHANNEL = 'audit';
    private const PERFORMANCE_CHANNEL = 'performance';

    /**
     * Log a cloud storage operation start
     */
    public function logOperationStart(
        string $operation,
        string $provider,
        User $user,
        array $context = []
    ): string {
        $operationId = $this->generateOperationId();
        
        $logContext = $this->buildBaseContext($operation, $provider, $user, $operationId) + [
            'operation_status' => 'started',
            'started_at' => now()->toISOString(),
        ] + $context;

        Log::channel(self::LOG_CHANNEL)->info("Cloud storage operation started: {$operation}", $logContext);
        
        return $operationId;
    }

    /**
     * Log a successful cloud storage operation
     */
    public function logOperationSuccess(
        string $operationId,
        string $operation,
        string $provider,
        User $user,
        array $context = [],
        ?float $durationMs = null
    ): void {
        $logContext = $this->buildBaseContext($operation, $provider, $user, $operationId) + [
            'operation_status' => 'success',
            'completed_at' => now()->toISOString(),
            'duration_ms' => $durationMs,
        ] + $context;

        Log::channel(self::LOG_CHANNEL)->info("Cloud storage operation successful: {$operation}", $logContext);
        
        // Log to audit channel for successful operations
        Log::channel(self::AUDIT_CHANNEL)->info("Cloud storage success: {$operation}", [
            'operation_id' => $operationId,
            'user_id' => $user->id,
            'provider' => $provider,
            'operation' => $operation,
            'duration_ms' => $durationMs,
        ]);

        // Log performance metrics if duration is provided
        if ($durationMs !== null) {
            $this->logPerformanceMetrics($operation, $provider, $durationMs, 'success', $context);
        }
    }

    /**
     * Log a failed cloud storage operation
     */
    public function logOperationFailure(
        string $operationId,
        string $operation,
        string $provider,
        User $user,
        CloudStorageErrorType $errorType,
        string $errorMessage,
        array $context = [],
        ?float $durationMs = null,
        ?\Throwable $exception = null
    ): void {
        $logContext = $this->buildBaseContext($operation, $provider, $user, $operationId) + [
            'operation_status' => 'failed',
            'failed_at' => now()->toISOString(),
            'error_type' => $errorType->value,
            'error_message' => $errorMessage,
            'duration_ms' => $durationMs,
            'error_classification' => $this->getErrorClassification($errorType),
            'is_retryable' => $this->isRetryableError($errorType),
            'requires_user_action' => $this->requiresUserAction($errorType),
        ] + $context;

        if ($exception) {
            $logContext['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        Log::channel(self::LOG_CHANNEL)->error("Cloud storage operation failed: {$operation}", $logContext);
        
        // Log to audit channel for failed operations
        Log::channel(self::AUDIT_CHANNEL)->warning("Cloud storage failure: {$operation}", [
            'operation_id' => $operationId,
            'user_id' => $user->id,
            'provider' => $provider,
            'operation' => $operation,
            'error_type' => $errorType->value,
            'error_message' => $errorMessage,
            'duration_ms' => $durationMs,
        ]);

        // Log performance metrics for failures
        if ($durationMs !== null) {
            $this->logPerformanceMetrics($operation, $provider, $durationMs, 'failure', [
                'error_type' => $errorType->value,
            ]);
        }
    }

    /**
     * Log retry decision and context
     */
    public function logRetryDecision(
        string $operationId,
        string $operation,
        string $provider,
        User $user,
        CloudStorageErrorType $errorType,
        int $attemptNumber,
        bool $willRetry,
        ?int $retryDelaySeconds = null,
        array $context = []
    ): void {
        $logContext = $this->buildBaseContext($operation, $provider, $user, $operationId) + [
            'retry_decision' => $willRetry ? 'retry' : 'no_retry',
            'attempt_number' => $attemptNumber,
            'error_type' => $errorType->value,
            'retry_delay_seconds' => $retryDelaySeconds,
            'retry_reason' => $this->getRetryReason($errorType, $willRetry),
        ] + $context;

        $level = $willRetry ? 'info' : 'warning';
        $message = $willRetry 
            ? "Cloud storage operation will be retried: {$operation} (attempt {$attemptNumber})"
            : "Cloud storage operation will not be retried: {$operation} (attempt {$attemptNumber})";

        Log::channel(self::LOG_CHANNEL)->log($level, $message, $logContext);
    }

    /**
     * Log connection health status change
     */
    public function logHealthStatusChange(
        string $provider,
        User $user,
        string $previousStatus,
        string $newStatus,
        ?CloudStorageErrorType $errorType = null,
        ?string $errorMessage = null,
        array $context = []
    ): void {
        $logContext = [
            'event_type' => 'health_status_change',
            'provider' => $provider,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'status_changed_at' => now()->toISOString(),
            'error_type' => $errorType?->value,
            'error_message' => $errorMessage,
            'status_severity' => $this->getStatusSeverity($newStatus),
        ] + $context;

        $level = match ($newStatus) {
            'healthy' => 'info',
            'degraded' => 'warning',
            'unhealthy', 'disconnected' => 'error',
            default => 'info',
        };

        Log::channel(self::LOG_CHANNEL)->log($level, "Cloud storage health status changed: {$previousStatus} → {$newStatus}", $logContext);
        
        // Log significant status changes to audit channel
        if (in_array($newStatus, ['unhealthy', 'disconnected']) || $previousStatus === 'unhealthy' && $newStatus === 'healthy') {
            Log::channel(self::AUDIT_CHANNEL)->info("Cloud storage health status change", [
                'user_id' => $user->id,
                'provider' => $provider,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'error_type' => $errorType?->value,
            ]);
        }
    }

    /**
     * Log token refresh operation
     */
    public function logTokenRefresh(
        string $provider,
        User $user,
        bool $success,
        ?Carbon $newExpiresAt = null,
        ?string $errorMessage = null,
        array $context = []
    ): void {
        $logContext = [
            'event_type' => 'token_refresh',
            'provider' => $provider,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'refresh_status' => $success ? 'success' : 'failed',
            'refreshed_at' => now()->toISOString(),
            'new_expires_at' => $newExpiresAt?->toISOString(),
            'error_message' => $errorMessage,
        ] + $context;

        $level = $success ? 'info' : 'error';
        $message = $success 
            ? "Cloud storage token refreshed successfully: {$provider}"
            : "Cloud storage token refresh failed: {$provider}";

        Log::channel(self::LOG_CHANNEL)->log($level, $message, $logContext);
        
        // Log token refresh events to audit channel
        Log::channel(self::AUDIT_CHANNEL)->info("Token refresh: {$provider}", [
            'user_id' => $user->id,
            'provider' => $provider,
            'success' => $success,
            'new_expires_at' => $newExpiresAt?->toISOString(),
        ]);
    }

    /**
     * Log OAuth authentication flow events
     */
    public function logOAuthEvent(
        string $provider,
        User $user,
        string $event,
        bool $success,
        ?string $errorMessage = null,
        array $context = []
    ): void {
        $logContext = [
            'event_type' => 'oauth_event',
            'oauth_event' => $event,
            'provider' => $provider,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'oauth_status' => $success ? 'success' : 'failed',
            'event_at' => now()->toISOString(),
            'error_message' => $errorMessage,
        ] + $context;

        $level = $success ? 'info' : 'error';
        $message = $success 
            ? "OAuth event successful: {$event} for {$provider}"
            : "OAuth event failed: {$event} for {$provider}";

        Log::channel(self::LOG_CHANNEL)->log($level, $message, $logContext);
        
        // Log OAuth events to audit channel for security monitoring
        Log::channel(self::AUDIT_CHANNEL)->info("OAuth event: {$event}", [
            'user_id' => $user->id,
            'provider' => $provider,
            'event' => $event,
            'success' => $success,
        ]);
    }

    /**
     * Log performance metrics for cloud storage operations
     */
    public function logPerformanceMetrics(
        string $operation,
        string $provider,
        float $durationMs,
        string $outcome,
        array $context = []
    ): void {
        $logContext = [
            'metric_type' => 'operation_performance',
            'operation' => $operation,
            'provider' => $provider,
            'duration_ms' => $durationMs,
            'outcome' => $outcome,
            'performance_category' => $this->getPerformanceCategory($durationMs),
            'measured_at' => now()->toISOString(),
        ] + $context;

        Log::channel(self::PERFORMANCE_CHANNEL)->info("Cloud storage performance: {$operation}", $logContext);
    }

    /**
     * Log bulk operation summary
     */
    public function logBulkOperationSummary(
        string $operation,
        string $provider,
        User $user,
        int $totalItems,
        int $successCount,
        int $failureCount,
        float $totalDurationMs,
        array $errorSummary = [],
        array $context = []
    ): void {
        $logContext = [
            'event_type' => 'bulk_operation_summary',
            'operation' => $operation,
            'provider' => $provider,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'total_items' => $totalItems,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'success_rate' => $totalItems > 0 ? round(($successCount / $totalItems) * 100, 2) : 0,
            'total_duration_ms' => $totalDurationMs,
            'average_duration_ms' => $totalItems > 0 ? round($totalDurationMs / $totalItems, 2) : 0,
            'error_summary' => $errorSummary,
            'completed_at' => now()->toISOString(),
        ] + $context;

        Log::channel(self::LOG_CHANNEL)->info("Bulk cloud storage operation completed: {$operation}", $logContext);
        
        // Log bulk operation summary to audit channel
        Log::channel(self::AUDIT_CHANNEL)->info("Bulk operation: {$operation}", [
            'user_id' => $user->id,
            'provider' => $provider,
            'total_items' => $totalItems,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'total_duration_ms' => $totalDurationMs,
        ]);
    }

    /**
     * Build base context for all log entries
     */
    private function buildBaseContext(string $operation, string $provider, User $user, string $operationId): array
    {
        return [
            'operation_id' => $operationId,
            'operation' => $operation,
            'provider' => $provider,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role?->value,
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
        ];
    }

    /**
     * Generate unique operation ID for tracking
     */
    private function generateOperationId(): string
    {
        return 'cs_' . Str::random(12) . '_' . now()->timestamp;
    }

    /**
     * Get error classification for logging
     */
    private function getErrorClassification(CloudStorageErrorType $errorType): string
    {
        return match ($errorType) {
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => 'authentication',
            CloudStorageErrorType::API_QUOTA_EXCEEDED,
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED => 'quota',
            CloudStorageErrorType::NETWORK_ERROR => 'network',
            CloudStorageErrorType::FILE_NOT_FOUND,
            CloudStorageErrorType::FOLDER_ACCESS_DENIED => 'access',
            CloudStorageErrorType::INVALID_FILE_TYPE => 'validation',
            default => 'unknown',
        };
    }

    /**
     * Check if error type is retryable
     */
    private function isRetryableError(CloudStorageErrorType $errorType): bool
    {
        return match ($errorType) {
            CloudStorageErrorType::NETWORK_ERROR,
            CloudStorageErrorType::API_QUOTA_EXCEEDED => true,
            default => false,
        };
    }

    /**
     * Check if error requires user action
     */
    private function requiresUserAction(CloudStorageErrorType $errorType): bool
    {
        return match ($errorType) {
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED => true,
            default => false,
        };
    }

    /**
     * Get retry reason for logging
     */
    private function getRetryReason(CloudStorageErrorType $errorType, bool $willRetry): string
    {
        if (!$willRetry) {
            return match ($errorType) {
                CloudStorageErrorType::TOKEN_EXPIRED => 'Token expired - requires user reconnection',
                CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => 'Insufficient permissions - requires user action',
                CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED => 'Storage quota exceeded - requires user action',
                CloudStorageErrorType::FILE_NOT_FOUND => 'File not found - permanent failure',
                CloudStorageErrorType::INVALID_FILE_TYPE => 'Invalid file type - permanent failure',
                default => 'Maximum retry attempts reached',
            };
        }

        return match ($errorType) {
            CloudStorageErrorType::NETWORK_ERROR => 'Network error - temporary issue',
            CloudStorageErrorType::API_QUOTA_EXCEEDED => 'API quota exceeded - will retry after delay',
            default => 'Retryable error detected',
        };
    }

    /**
     * Get status severity for logging
     */
    private function getStatusSeverity(string $status): string
    {
        return match ($status) {
            'healthy' => 'low',
            'degraded' => 'medium',
            'unhealthy' => 'high',
            'disconnected' => 'critical',
            default => 'unknown',
        };
    }

    /**
     * Get performance category based on duration
     */
    private function getPerformanceCategory(float $durationMs): string
    {
        return match (true) {
            $durationMs < 1000 => 'fast',
            $durationMs < 5000 => 'normal',
            $durationMs < 15000 => 'slow',
            default => 'very_slow',
        };
    }
}