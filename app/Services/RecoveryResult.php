<?php

namespace App\Services;

use Exception;

/**
 * Value object representing the result of a connection recovery attempt.
 */
class RecoveryResult
{
    public function __construct(
        private bool $successful,
        private string $message,
        private RecoveryStrategy $strategy,
        private ?Exception $exception = null
    ) {}

    /**
     * Create a successful recovery result.
     */
    public static function success(string $message, RecoveryStrategy $strategy): self
    {
        return new self(true, $message, $strategy);
    }

    /**
     * Create a failed recovery result.
     */
    public static function failure(string $message, RecoveryStrategy $strategy, ?Exception $exception = null): self
    {
        return new self(false, $message, $strategy, $exception);
    }

    /**
     * Check if the recovery was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * Check if the recovery failed.
     */
    public function isFailed(): bool
    {
        return !$this->successful;
    }

    /**
     * Get the result message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get the error message (alias for getMessage when failed).
     */
    public function getErrorMessage(): string
    {
        return $this->message;
    }

    /**
     * Get the recovery strategy that was used.
     */
    public function getStrategy(): RecoveryStrategy
    {
        return $this->strategy;
    }

    /**
     * Get the exception if one occurred.
     */
    public function getException(): ?Exception
    {
        return $this->exception;
    }

    /**
     * Convert to array for logging or serialization.
     */
    public function toArray(): array
    {
        return [
            'successful' => $this->successful,
            'message' => $this->message,
            'strategy' => $this->strategy->value,
            'exception' => $this->exception ? [
                'class' => get_class($this->exception),
                'message' => $this->exception->getMessage(),
                'code' => $this->exception->getCode(),
            ] : null,
        ];
    }
}