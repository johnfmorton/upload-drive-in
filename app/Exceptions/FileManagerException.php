<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FileManagerException extends Exception
{
    protected string $userMessage;
    protected array $context;
    protected bool $isRetryable;

    public function __construct(
        string $message,
        string $userMessage,
        int $code = 500,
        ?Exception $previous = null,
        array $context = [],
        bool $isRetryable = false
    ) {
        parent::__construct($message, $code, $previous);
        $this->userMessage = $userMessage;
        $this->context = $context;
        $this->isRetryable = $isRetryable;
        
        // Log the exception when it's created
        $this->logException();
    }

    /**
     * Get user-friendly message for display.
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
     * Check if the error is retryable.
     */
    public function isRetryable(): bool
    {
        return $this->isRetryable;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return $this->renderJson();
        }

        return $this->renderRedirect();
    }

    /**
     * Render the exception as a JSON response.
     */
    protected function renderJson(): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->userMessage,
            'error_code' => $this->context['type'] ?? 'unknown_error',
        ];

        if ($this->isRetryable) {
            $response['is_retryable'] = true;
        }

        // Include additional context for debugging in non-production environments
        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($this),
                'message' => $this->getMessage(),
                'code' => $this->getCode(),
                'context' => $this->context,
                'file' => $this->getFile(),
                'line' => $this->getLine(),
            ];
        }

        return response()->json($response, $this->getHttpStatusCode());
    }

    /**
     * Render the exception as a redirect response.
     */
    protected function renderRedirect(): RedirectResponse
    {
        $route = $this->getRedirectRoute();
        
        return redirect()
            ->route($route)
            ->with('error', $this->userMessage)
            ->with('error_code', $this->context['type'] ?? 'unknown_error')
            ->with('is_retryable', $this->isRetryable);
    }

    /**
     * Get the appropriate HTTP status code based on the exception code.
     */
    protected function getHttpStatusCode(): int
    {
        // Map exception codes to HTTP status codes
        return match ($this->getCode()) {
            400 => 400, // Bad Request
            401 => 401, // Unauthorized
            403 => 403, // Forbidden
            404 => 404, // Not Found
            429 => 429, // Too Many Requests
            default => 500, // Internal Server Error
        };
    }

    /**
     * Get the appropriate redirect route based on the exception context.
     */
    protected function getRedirectRoute(): string
    {
        // Default to file manager index
        return 'admin.file-manager.index';
    }

    /**
     * Log the exception with appropriate level based on severity.
     */
    protected function logException(): void
    {
        $logData = [
            'message' => $this->getMessage(),
            'user_message' => $this->userMessage,
            'code' => $this->getCode(),
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];

        // Determine log level based on HTTP status code
        $logLevel = match ($this->getHttpStatusCode()) {
            400, 401, 403, 404 => 'warning',
            default => 'error',
        };

        Log::$logLevel(get_class($this) . ': ' . $this->getMessage(), $logData);
    }

    /**
     * Create a file not found exception.
     */
    public static function fileNotFound(int $fileId, string $operation = 'access'): self
    {
        return new self(
            message: "File not found: {$fileId}",
            userMessage: "The requested file could not be found.",
            code: 404,
            context: [
                'file_id' => $fileId,
                'operation' => $operation,
                'type' => 'file_not_found'
            ]
        );
    }

    /**
     * Create a database error exception.
     */
    public static function databaseError(string $operation, \Exception $previous = null): self
    {
        return new self(
            message: "Database error during {$operation}: " . ($previous ? $previous->getMessage() : 'Unknown error'),
            userMessage: "A database error occurred. Please try again or contact support.",
            code: 500,
            previous: $previous,
            context: [
                'operation' => $operation,
                'type' => 'database_error'
            ],
            isRetryable: true
        );
    }

    /**
     * Create a storage error exception.
     */
    public static function storageError(string $operation, \Exception $previous = null): self
    {
        return new self(
            message: "Storage error during {$operation}: " . ($previous ? $previous->getMessage() : 'Unknown error'),
            userMessage: "A file storage error occurred. Please try again or contact support.",
            code: 500,
            previous: $previous,
            context: [
                'operation' => $operation,
                'type' => 'storage_error'
            ],
            isRetryable: true
        );
    }

    /**
     * Create a validation error exception.
     */
    public static function validationError(string $message, array $errors = []): self
    {
        return new self(
            message: "Validation error: {$message}",
            userMessage: $message,
            code: 400,
            context: [
                'errors' => $errors,
                'type' => 'validation_error'
            ]
        );
    }

    /**
     * Create a server error exception.
     */
    public static function serverError(string $operation, \Exception $previous = null): self
    {
        return new self(
            message: "Server error during {$operation}: " . ($previous ? $previous->getMessage() : 'Unknown error'),
            userMessage: "An unexpected error occurred. Please try again later.",
            code: 500,
            previous: $previous,
            context: [
                'operation' => $operation,
                'type' => 'server_error'
            ],
            isRetryable: true
        );
    }
}