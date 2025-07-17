<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FileManagerException extends Exception
{
    protected array $context = [];
    protected string $userMessage;
    protected bool $isRetryable = false;

    public function __construct(
        string $message,
        string $userMessage = null,
        int $code = 0,
        Exception $previous = null,
        array $context = [],
        bool $isRetryable = false
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->userMessage = $userMessage ?? $this->getDefaultUserMessage();
        $this->context = $context;
        $this->isRetryable = $isRetryable;
    }

    /**
     * Get user-friendly error message.
     */
    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    /**
     * Get additional context information.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Check if the operation can be retried.
     */
    public function isRetryable(): bool
    {
        return $this->isRetryable;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getUserMessage(),
            'error_code' => $this->getCode(),
        ];

        if ($this->isRetryable()) {
            $response['retryable'] = true;
        }

        if (config('app.debug') && !empty($this->context)) {
            $response['debug'] = [
                'technical_message' => $this->getMessage(),
                'context' => $this->getContext(),
            ];
        }

        return response()->json($response, $this->getHttpStatusCode());
    }

    /**
     * Get appropriate HTTP status code for the error.
     */
    protected function getHttpStatusCode(): int
    {
        return match ($this->getCode()) {
            404 => 404,
            403 => 403,
            422 => 422,
            429 => 429,
            default => 500,
        };
    }

    /**
     * Get default user-friendly message based on error code.
     */
    protected function getDefaultUserMessage(): string
    {
        return match ($this->getCode()) {
            404 => 'The requested file could not be found.',
            403 => 'You do not have permission to access this file.',
            422 => 'The request could not be processed due to invalid data.',
            429 => 'Too many requests. Please try again later.',
            default => 'An unexpected error occurred. Please try again.',
        };
    }
}