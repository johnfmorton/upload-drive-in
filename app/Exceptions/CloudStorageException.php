<?php

namespace App\Exceptions;

use App\Enums\CloudStorageErrorType;
use Exception;
use Throwable;

/**
 * Exception for cloud storage operations
 * 
 * Provides structured error information for cloud storage failures
 * with error type classification and context data
 */
class CloudStorageException extends Exception
{
    public function __construct(
        string $message = '',
        public readonly ?CloudStorageErrorType $errorType = null,
        public readonly array $context = [],
        public readonly ?string $provider = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for token expiration
     *
     * @param string $provider Provider name
     * @param array $context Additional context
     * @param Throwable|null $previous Previous exception
     * @return self
     */
    public static function tokenExpired(
        string $provider,
        array $context = [],
        ?Throwable $previous = null
    ): self {
        return new self(
            message: "Authentication token expired for {$provider}",
            errorType: CloudStorageErrorType::TOKEN_EXPIRED,
            context: $context,
            provider: $provider,
            previous: $previous
        );
    }

    /**
     * Create exception for insufficient permissions
     *
     * @param string $provider Provider name
     * @param array $context Additional context
     * @param Throwable|null $previous Previous exception
     * @return self
     */
    public static function insufficientPermissions(
        string $provider,
        array $context = [],
        ?Throwable $previous = null
    ): self {
        return new self(
            message: "Insufficient permissions for {$provider}",
            errorType: CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            context: $context,
            provider: $provider,
            previous: $previous
        );
    }

    /**
     * Create exception for API quota exceeded
     *
     * @param string $provider Provider name
     * @param array $context Additional context
     * @param Throwable|null $previous Previous exception
     * @return self
     */
    public static function quotaExceeded(
        string $provider,
        array $context = [],
        ?Throwable $previous = null
    ): self {
        return new self(
            message: "API quota exceeded for {$provider}",
            errorType: CloudStorageErrorType::API_QUOTA_EXCEEDED,
            context: $context,
            provider: $provider,
            previous: $previous
        );
    }

    /**
     * Create exception for network errors
     *
     * @param string $provider Provider name
     * @param array $context Additional context
     * @param Throwable|null $previous Previous exception
     * @return self
     */
    public static function networkError(
        string $provider,
        array $context = [],
        ?Throwable $previous = null
    ): self {
        return new self(
            message: "Network error connecting to {$provider}",
            errorType: CloudStorageErrorType::NETWORK_ERROR,
            context: $context,
            provider: $provider,
            previous: $previous
        );
    }

    /**
     * Create exception for storage quota exceeded
     *
     * @param string $provider Provider name
     * @param array $context Additional context
     * @param Throwable|null $previous Previous exception
     * @return self
     */
    public static function storageQuotaExceeded(
        string $provider,
        array $context = [],
        ?Throwable $previous = null
    ): self {
        return new self(
            message: "Storage quota exceeded for {$provider}",
            errorType: CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            context: $context,
            provider: $provider,
            previous: $previous
        );
    }

    /**
     * Create exception for unknown errors
     *
     * @param string $provider Provider name
     * @param string $message Error message
     * @param array $context Additional context
     * @param Throwable|null $previous Previous exception
     * @return self
     */
    public static function unknown(
        string $provider,
        string $message = 'Unknown error occurred',
        array $context = [],
        ?Throwable $previous = null
    ): self {
        return new self(
            message: $message,
            errorType: CloudStorageErrorType::UNKNOWN_ERROR,
            context: $context,
            provider: $provider,
            previous: $previous
        );
    }

    /**
     * Create exception with error type classification
     *
     * @param CloudStorageErrorType $errorType The classified error type
     * @param string $provider Provider name
     * @param array $context Additional context
     * @param Throwable|null $previous Previous exception
     * @return self
     */
    public static function create(
        CloudStorageErrorType $errorType,
        string $provider,
        array $context = [],
        ?Throwable $previous = null
    ): self {
        $message = $context['original_message'] ?? $errorType->getDescription();
        
        return new self(
            message: "{$provider} error: {$message}",
            errorType: $errorType,
            context: $context,
            provider: $provider,
            previous: $previous
        );
    }

    /**
     * Get the error type
     *
     * @return CloudStorageErrorType|null
     */
    public function getErrorType(): ?CloudStorageErrorType
    {
        return $this->errorType;
    }

    /**
     * Get the context data
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get the provider name
     *
     * @return string|null
     */
    public function getProvider(): ?string
    {
        return $this->provider;
    }

    /**
     * Check if this exception requires user intervention
     *
     * @return bool
     */
    public function requiresUserIntervention(): bool
    {
        return $this->errorType?->requiresUserIntervention() ?? false;
    }

    /**
     * Check if this exception is recoverable through retry
     *
     * @return bool
     */
    public function isRecoverable(): bool
    {
        return $this->errorType?->isRecoverable() ?? false;
    }

    /**
     * Get the error severity
     *
     * @return string
     */
    public function getSeverity(): string
    {
        return $this->errorType?->getSeverity() ?? 'high';
    }

    /**
     * Convert to array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'error_type' => $this->errorType?->value,
            'provider' => $this->provider,
            'context' => $this->context,
            'code' => $this->getCode(),
            'requires_user_intervention' => $this->requiresUserIntervention(),
            'is_recoverable' => $this->isRecoverable(),
            'severity' => $this->getSeverity(),
        ];
    }
}