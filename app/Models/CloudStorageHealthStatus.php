<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloudStorageHealthStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'status',
        'consolidated_status',
        'last_successful_operation_at',
        'consecutive_failures',
        'last_error_type',
        'last_error_message',
        'last_error_context',
        'token_expires_at',
        'last_token_refresh_attempt_at',
        'token_refresh_failures',
        'operational_test_result',
        'requires_reconnection',
        'provider_specific_data',
        // New fields for real-time validation
        'last_live_validation_at',
        'live_validation_result',
        'api_connectivity_last_tested_at',
        'api_connectivity_result',
    ];

    protected $casts = [
        'provider_specific_data' => 'array',
        'operational_test_result' => 'array',
        'last_error_context' => 'array',
        'last_successful_operation_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'last_token_refresh_attempt_at' => 'datetime',
        'requires_reconnection' => 'boolean',
        'consecutive_failures' => 'integer',
        'token_refresh_failures' => 'integer',
        // New fields for real-time validation
        'last_live_validation_at' => 'datetime',
        'live_validation_result' => 'array',
        'api_connectivity_last_tested_at' => 'datetime',
        'api_connectivity_result' => 'array',
    ];

    /**
     * Get the user that owns this health status.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the connection is healthy.
     */
    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    /**
     * Check if the connection is degraded.
     */
    public function isDegraded(): bool
    {
        return $this->status === 'degraded';
    }

    /**
     * Check if the connection is unhealthy.
     */
    public function isUnhealthy(): bool
    {
        return $this->status === 'unhealthy';
    }

    /**
     * Check if the connection is disconnected.
     */
    public function isDisconnected(): bool
    {
        return $this->status === 'disconnected';
    }

    /**
     * Get a user-friendly status message.
     */
    public function getStatusMessage(): string
    {
        return match ($this->status) {
            'healthy' => 'Connection is working properly',
            'degraded' => 'Connection has some issues but is functional',
            'unhealthy' => 'Connection has significant problems',
            'disconnected' => 'Connection is not established',
            default => 'Unknown status',
        };
    }

    /**
     * Get the time since last successful operation in human readable format.
     */
    public function getTimeSinceLastSuccess(): ?string
    {
        if (!$this->last_successful_operation_at) {
            return null;
        }

        return $this->last_successful_operation_at->diffForHumans();
    }

    /**
     * Check if token is expiring soon (within 24 hours).
     */
    public function isTokenExpiringSoon(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isBefore(now()->addDay());
    }

    /**
     * Check if token has expired.
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Get consolidated status message that eliminates confusion.
     */
    public function getConsolidatedStatusMessage(): string
    {
        return match ($this->consolidated_status) {
            'healthy' => 'Connection is working properly',
            'authentication_required' => 'Please reconnect your account',
            'connection_issues' => 'Experiencing connectivity problems',
            'not_connected' => 'Account not connected',
            default => 'Status unknown',
        };
    }

    /**
     * Check if automatic token refresh is working.
     */
    public function isTokenRefreshWorking(): bool
    {
        return $this->token_refresh_failures < 3;
    }

    /**
     * Get the last error type as an enum instance.
     */
    public function getLastErrorTypeEnum(): ?\App\Enums\CloudStorageErrorType
    {
        if (!$this->last_error_type) {
            return null;
        }

        return \App\Enums\CloudStorageErrorType::tryFrom($this->last_error_type);
    }

    /**
     * Check if the last error requires user intervention.
     */
    public function lastErrorRequiresUserIntervention(): bool
    {
        $errorType = $this->getLastErrorTypeEnum();
        if (!$errorType) {
            return false;
        }

        return $errorType->requiresUserIntervention();
    }

    /**
     * Check if the last error is recoverable through retry.
     */
    public function lastErrorIsRecoverable(): bool
    {
        $errorType = $this->getLastErrorTypeEnum();
        if (!$errorType) {
            return false;
        }

        return $errorType->isRecoverable();
    }

    /**
     * Get the severity of the last error.
     */
    public function getLastErrorSeverity(): ?string
    {
        $errorType = $this->getLastErrorTypeEnum();
        if (!$errorType) {
            return null;
        }

        return $errorType->getSeverity();
    }

    /**
     * Check if exponential backoff should be applied for token refresh.
     */
    public function shouldApplyTokenRefreshBackoff(): bool
    {
        if ($this->token_refresh_failures < 2 || !$this->last_token_refresh_attempt_at) {
            return false;
        }

        $backoffDelay = $this->calculateTokenRefreshBackoffDelay();
        $nextAllowedAttempt = $this->last_token_refresh_attempt_at->addSeconds($backoffDelay);

        return now()->isBefore($nextAllowedAttempt);
    }

    /**
     * Calculate the exponential backoff delay for token refresh.
     */
    public function calculateTokenRefreshBackoffDelay(): int
    {
        $baseDelay = 30; // 30 seconds base delay
        $maxDelay = 300; // 5 minutes maximum delay
        
        $delay = $baseDelay * pow(2, min($this->token_refresh_failures - 1, 4));
        return min($delay, $maxDelay);
    }

    /**
     * Get the time remaining until next token refresh attempt is allowed.
     */
    public function getTokenRefreshBackoffTimeRemaining(): ?int
    {
        if (!$this->shouldApplyTokenRefreshBackoff()) {
            return null;
        }

        $backoffDelay = $this->calculateTokenRefreshBackoffDelay();
        $nextAllowedAttempt = $this->last_token_refresh_attempt_at->addSeconds($backoffDelay);

        return max(0, now()->diffInSeconds($nextAllowedAttempt, false));
    }

    /**
     * Get a user-friendly error message with context.
     */
    public function getDetailedErrorMessage(): ?string
    {
        if (!$this->last_error_message) {
            return null;
        }

        $message = $this->last_error_message;
        $context = $this->last_error_context;

        if ($context && isset($context['requires_user_intervention']) && $context['requires_user_intervention']) {
            $message .= ' (User action required)';
        } elseif ($context && isset($context['is_recoverable']) && $context['is_recoverable']) {
            $message .= ' (Will retry automatically)';
        }

        return $message;
    }
}