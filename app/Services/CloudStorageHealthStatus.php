<?php

namespace App\Services;

use App\Enums\CloudStorageErrorType;
use Carbon\Carbon;

/**
 * Represents the health status of a cloud storage connection
 * 
 * Used to track connection health, errors, and operational status
 * across all cloud storage providers
 */
class CloudStorageHealthStatus
{
    public const STATUS_HEALTHY = 'healthy';
    public const STATUS_DEGRADED = 'degraded';
    public const STATUS_UNHEALTHY = 'unhealthy';
    public const STATUS_DISCONNECTED = 'disconnected';

    public function __construct(
        public readonly string $provider,
        public readonly string $status,
        public readonly ?Carbon $lastSuccessfulOperation = null,
        public readonly int $consecutiveFailures = 0,
        public readonly ?CloudStorageErrorType $lastErrorType = null,
        public readonly ?string $lastErrorMessage = null,
        public readonly ?Carbon $tokenExpiresAt = null,
        public readonly bool $requiresReconnection = false,
        public readonly array $providerSpecificData = []
    ) {}

    /**
     * Create a healthy status instance
     *
     * @param string $provider Provider name
     * @param Carbon|null $lastSuccessfulOperation Last successful operation timestamp
     * @param Carbon|null $tokenExpiresAt Token expiration timestamp
     * @param array $providerSpecificData Provider-specific health data
     * @return self
     */
    public static function healthy(
        string $provider,
        ?Carbon $lastSuccessfulOperation = null,
        ?Carbon $tokenExpiresAt = null,
        array $providerSpecificData = []
    ): self {
        return new self(
            provider: $provider,
            status: self::STATUS_HEALTHY,
            lastSuccessfulOperation: $lastSuccessfulOperation,
            tokenExpiresAt: $tokenExpiresAt,
            providerSpecificData: $providerSpecificData
        );
    }

    /**
     * Create a degraded status instance
     *
     * @param string $provider Provider name
     * @param int $consecutiveFailures Number of consecutive failures
     * @param CloudStorageErrorType|null $lastErrorType Last error type
     * @param string|null $lastErrorMessage Last error message
     * @param Carbon|null $lastSuccessfulOperation Last successful operation timestamp
     * @param array $providerSpecificData Provider-specific health data
     * @return self
     */
    public static function degraded(
        string $provider,
        int $consecutiveFailures = 1,
        ?CloudStorageErrorType $lastErrorType = null,
        ?string $lastErrorMessage = null,
        ?Carbon $lastSuccessfulOperation = null,
        array $providerSpecificData = []
    ): self {
        return new self(
            provider: $provider,
            status: self::STATUS_DEGRADED,
            lastSuccessfulOperation: $lastSuccessfulOperation,
            consecutiveFailures: $consecutiveFailures,
            lastErrorType: $lastErrorType,
            lastErrorMessage: $lastErrorMessage,
            providerSpecificData: $providerSpecificData
        );
    }

    /**
     * Create an unhealthy status instance
     *
     * @param string $provider Provider name
     * @param int $consecutiveFailures Number of consecutive failures
     * @param CloudStorageErrorType|null $lastErrorType Last error type
     * @param string|null $lastErrorMessage Last error message
     * @param bool $requiresReconnection Whether reconnection is required
     * @param Carbon|null $lastSuccessfulOperation Last successful operation timestamp
     * @param array $providerSpecificData Provider-specific health data
     * @return self
     */
    public static function unhealthy(
        string $provider,
        int $consecutiveFailures,
        ?CloudStorageErrorType $lastErrorType = null,
        ?string $lastErrorMessage = null,
        bool $requiresReconnection = false,
        ?Carbon $lastSuccessfulOperation = null,
        array $providerSpecificData = []
    ): self {
        return new self(
            provider: $provider,
            status: self::STATUS_UNHEALTHY,
            lastSuccessfulOperation: $lastSuccessfulOperation,
            consecutiveFailures: $consecutiveFailures,
            lastErrorType: $lastErrorType,
            lastErrorMessage: $lastErrorMessage,
            requiresReconnection: $requiresReconnection,
            providerSpecificData: $providerSpecificData
        );
    }

    /**
     * Create a disconnected status instance
     *
     * @param string $provider Provider name
     * @param array $providerSpecificData Provider-specific health data
     * @return self
     */
    public static function disconnected(
        string $provider,
        array $providerSpecificData = []
    ): self {
        return new self(
            provider: $provider,
            status: self::STATUS_DISCONNECTED,
            requiresReconnection: true,
            providerSpecificData: $providerSpecificData
        );
    }

    /**
     * Check if the connection is healthy
     *
     * @return bool True if status is healthy
     */
    public function isHealthy(): bool
    {
        return $this->status === self::STATUS_HEALTHY;
    }

    /**
     * Check if the connection is degraded
     *
     * @return bool True if status is degraded
     */
    public function isDegraded(): bool
    {
        return $this->status === self::STATUS_DEGRADED;
    }

    /**
     * Check if the connection is unhealthy
     *
     * @return bool True if status is unhealthy
     */
    public function isUnhealthy(): bool
    {
        return $this->status === self::STATUS_UNHEALTHY;
    }

    /**
     * Check if the connection is disconnected
     *
     * @return bool True if status is disconnected
     */
    public function isDisconnected(): bool
    {
        return $this->status === self::STATUS_DISCONNECTED;
    }

    /**
     * Check if the token is expiring soon (within 24 hours)
     *
     * @return bool True if token expires within 24 hours
     */
    public function isTokenExpiringSoon(): bool
    {
        if (!$this->tokenExpiresAt) {
            return false;
        }

        return now()->diffInHours($this->tokenExpiresAt) <= 24;
    }

    /**
     * Get a human-readable status description
     *
     * @return string Status description
     */
    public function getStatusDescription(): string
    {
        return match ($this->status) {
            self::STATUS_HEALTHY => 'Connection is healthy and operational',
            self::STATUS_DEGRADED => "Connection is experiencing issues ({$this->consecutiveFailures} consecutive failures)",
            self::STATUS_UNHEALTHY => "Connection is unhealthy ({$this->consecutiveFailures} consecutive failures)",
            self::STATUS_DISCONNECTED => 'Connection is disconnected and requires authentication',
            default => 'Unknown status',
        };
    }

    /**
     * Convert to array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'status' => $this->status,
            'last_successful_operation' => $this->lastSuccessfulOperation?->toISOString(),
            'consecutive_failures' => $this->consecutiveFailures,
            'last_error_type' => $this->lastErrorType?->value,
            'last_error_message' => $this->lastErrorMessage,
            'token_expires_at' => $this->tokenExpiresAt?->toISOString(),
            'requires_reconnection' => $this->requiresReconnection,
            'provider_specific_data' => $this->providerSpecificData,
            'is_healthy' => $this->isHealthy(),
            'is_degraded' => $this->isDegraded(),
            'is_unhealthy' => $this->isUnhealthy(),
            'is_disconnected' => $this->isDisconnected(),
            'is_token_expiring_soon' => $this->isTokenExpiringSoon(),
            'status_description' => $this->getStatusDescription(),
        ];
    }
}