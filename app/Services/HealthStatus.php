<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Value object representing the health status validation results.
 * Used by RealTimeHealthValidator to encapsulate validation outcomes.
 */
class HealthStatus
{
    public function __construct(
        private readonly bool $isHealthy,
        private readonly string $status,
        private readonly ?string $errorMessage = null,
        private readonly ?string $errorType = null,
        private readonly ?array $validationDetails = null,
        private readonly ?Carbon $validatedAt = null,
        private readonly ?int $cacheTtlSeconds = null
    ) {}

    public function isHealthy(): bool
    {
        return $this->isHealthy;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    public function getValidationDetails(): ?array
    {
        return $this->validationDetails;
    }

    public function getValidatedAt(): ?Carbon
    {
        return $this->validatedAt;
    }

    public function getCacheTtlSeconds(): ?int
    {
        return $this->cacheTtlSeconds;
    }

    /**
     * Create a healthy status result.
     */
    public static function healthy(array $validationDetails = []): self
    {
        return new self(
            isHealthy: true,
            status: 'healthy',
            validationDetails: $validationDetails,
            validatedAt: now(),
            cacheTtlSeconds: 30 // Cache healthy status for 30 seconds
        );
    }

    /**
     * Create an authentication required status result.
     */
    public static function authenticationRequired(string $errorMessage, array $validationDetails = []): self
    {
        return new self(
            isHealthy: false,
            status: 'authentication_required',
            errorMessage: $errorMessage,
            errorType: 'authentication_error',
            validationDetails: $validationDetails,
            validatedAt: now(),
            cacheTtlSeconds: 10 // Cache error status for 10 seconds
        );
    }

    /**
     * Create a connection issues status result.
     */
    public static function connectionIssues(string $errorMessage, ?string $errorType = null, array $validationDetails = []): self
    {
        return new self(
            isHealthy: false,
            status: 'connection_issues',
            errorMessage: $errorMessage,
            errorType: $errorType ?? 'connection_error',
            validationDetails: $validationDetails,
            validatedAt: now(),
            cacheTtlSeconds: 10 // Cache error status for 10 seconds
        );
    }

    /**
     * Create a not connected status result.
     */
    public static function notConnected(string $errorMessage, array $validationDetails = []): self
    {
        return new self(
            isHealthy: false,
            status: 'not_connected',
            errorMessage: $errorMessage,
            errorType: 'not_connected',
            validationDetails: $validationDetails,
            validatedAt: now(),
            cacheTtlSeconds: 10 // Cache error status for 10 seconds
        );
    }

    /**
     * Create a status result from token validation error.
     */
    public static function fromTokenError(array $tokenResult): self
    {
        $errorMessage = $tokenResult['error'] ?? __('messages.health_status_token_validation_failed');
        $errorType = $tokenResult['error_type']?->value ?? 'token_error';
        $requiresUserIntervention = $tokenResult['requires_user_intervention'] ?? true;

        $status = $requiresUserIntervention ? 'authentication_required' : 'connection_issues';

        return new self(
            isHealthy: false,
            status: $status,
            errorMessage: $errorMessage,
            errorType: $errorType,
            validationDetails: $tokenResult,
            validatedAt: now(),
            cacheTtlSeconds: 10
        );
    }

    /**
     * Create a status result from API connectivity error.
     */
    public static function fromApiError(array $apiResult): self
    {
        $errorMessage = $apiResult['error'] ?? __('messages.health_status_api_connectivity_test_failed');
        $errorType = $apiResult['error_type'] ?? 'api_error';

        return new self(
            isHealthy: false,
            status: 'connection_issues',
            errorMessage: $errorMessage,
            errorType: $errorType,
            validationDetails: $apiResult,
            validatedAt: now(),
            cacheTtlSeconds: 10
        );
    }

    /**
     * Get the localized status name.
     */
    public function getLocalizedStatus(): string
    {
        return match ($this->status) {
            'healthy' => __('messages.health_status_healthy'),
            'authentication_required' => __('messages.health_status_authentication_required'),
            'connection_issues' => __('messages.health_status_connection_issues'),
            'not_connected' => __('messages.health_status_not_connected'),
            default => $this->status,
        };
    }

    /**
     * Get the localized error type name.
     */
    public function getLocalizedErrorType(): ?string
    {
        if (!$this->errorType) {
            return null;
        }

        return match ($this->errorType) {
            'authentication_error' => __('messages.health_status_authentication_error'),
            'connection_error' => __('messages.health_status_connection_error'),
            'token_error' => __('messages.health_status_token_error'),
            'api_error' => __('messages.health_status_api_error'),
            'not_connected' => __('messages.health_status_not_connected'),
            default => $this->errorType,
        };
    }

    /**
     * Convert to array for logging or storage.
     */
    public function toArray(): array
    {
        return [
            'is_healthy' => $this->isHealthy,
            'status' => $this->status,
            'error_message' => $this->errorMessage,
            'error_type' => $this->errorType,
            'validation_details' => $this->validationDetails,
            'validated_at' => $this->validatedAt?->toISOString(),
            'cache_ttl_seconds' => $this->cacheTtlSeconds,
        ];
    }

    /**
     * Convert to array with localized strings for display.
     */
    public function toLocalizedArray(): array
    {
        return [
            'is_healthy' => $this->isHealthy,
            'status' => $this->status,
            'status_localized' => $this->getLocalizedStatus(),
            'error_message' => $this->errorMessage,
            'error_type' => $this->errorType,
            'error_type_localized' => $this->getLocalizedErrorType(),
            'validation_details' => $this->validationDetails,
            'validated_at' => $this->validatedAt?->toISOString(),
            'cache_ttl_seconds' => $this->cacheTtlSeconds,
        ];
    }

    /**
     * Get consecutive failures count.
     * Note: Real-time health status doesn't track consecutive failures,
     * so this returns null to indicate it's not applicable for this context.
     */
    public function getConsecutiveFailures(): ?int
    {
        return null;
    }
}