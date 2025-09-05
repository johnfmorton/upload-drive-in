<?php

namespace App\Services;

use App\Enums\TokenRefreshErrorType;
use Exception;

/**
 * Value object representing the result of a token refresh operation
 */
class RefreshResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $message = null,
        public readonly ?TokenRefreshErrorType $errorType = null,
        public readonly ?Exception $exception = null,
        public readonly bool $wasAlreadyValid = false,
        public readonly bool $wasRefreshedByAnotherProcess = false,
        public readonly ?array $tokenData = null
    ) {}

    /**
     * Create a successful refresh result
     */
    public static function success(array $tokenData = null, string $message = null): self
    {
        return new self(
            success: true,
            message: $message ?? __('messages.token_refresh_success'),
            tokenData: $tokenData
        );
    }

    /**
     * Create a result indicating the token was already valid
     */
    public static function alreadyValid(string $message = null): self
    {
        return new self(
            success: true,
            message: $message ?? __('messages.token_already_valid'),
            wasAlreadyValid: true
        );
    }

    /**
     * Create a result indicating another process already refreshed the token
     */
    public static function refreshedByAnotherProcess(string $message = null): self
    {
        return new self(
            success: true,
            message: $message ?? __('messages.token_refreshed_by_another_process'),
            wasRefreshedByAnotherProcess: true
        );
    }

    /**
     * Create a failed refresh result
     */
    public static function failure(
        TokenRefreshErrorType $errorType,
        Exception $exception,
        string $message = null
    ): self {
        return new self(
            success: false,
            message: $message ?? $exception->getMessage(),
            errorType: $errorType,
            exception: $exception
        );
    }

    /**
     * Check if the refresh was successful (including already valid scenarios)
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if a new token was actually obtained
     */
    public function wasTokenRefreshed(): bool
    {
        return $this->success && !$this->wasAlreadyValid && !$this->wasRefreshedByAnotherProcess;
    }

    /**
     * Get the error type if the refresh failed
     */
    public function getErrorType(): ?TokenRefreshErrorType
    {
        return $this->errorType;
    }

    /**
     * Get the exception if the refresh failed
     */
    public function getException(): ?Exception
    {
        return $this->exception;
    }

    /**
     * Get a human-readable description of the result
     */
    public function getDescription(): string
    {
        if ($this->wasAlreadyValid) {
            return __('messages.token_already_valid_description');
        }

        if ($this->wasRefreshedByAnotherProcess) {
            return __('messages.token_refreshed_by_another_process_description');
        }

        if ($this->success) {
            return __('messages.token_refresh_success_description');
        }

        return __('messages.token_refresh_failed_description', ['message' => $this->message]);
    }

    /**
     * Convert to array for logging or serialization
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'error_type' => $this->errorType?->value,
            'was_already_valid' => $this->wasAlreadyValid,
            'was_refreshed_by_another_process' => $this->wasRefreshedByAnotherProcess,
            'has_token_data' => !empty($this->tokenData),
        ];
    }
}