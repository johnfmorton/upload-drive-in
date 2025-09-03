<?php

namespace App\Services;

use App\Enums\CloudStorageErrorType;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service for handling retry logic for cloud storage operations
 * 
 * This service provides intelligent retry mechanisms for transient failures
 * with exponential backoff and configurable retry policies.
 */
class CloudStorageRetryService
{
    /**
     * Default retry configuration
     */
    private const DEFAULT_CONFIG = [
        'max_attempts' => 3,
        'base_delay' => 1000, // milliseconds
        'max_delay' => 30000, // milliseconds
        'backoff_multiplier' => 2,
        'jitter' => true,
    ];

    /**
     * Error types that are retryable
     */
    private const RETRYABLE_ERRORS = [
        CloudStorageErrorType::NETWORK_ERROR,
        CloudStorageErrorType::SERVICE_UNAVAILABLE,
        CloudStorageErrorType::TIMEOUT,
        CloudStorageErrorType::API_QUOTA_EXCEEDED,
    ];

    /**
     * Error types that should not be retried
     */
    private const NON_RETRYABLE_ERRORS = [
        CloudStorageErrorType::INVALID_CREDENTIALS,
        CloudStorageErrorType::TOKEN_EXPIRED,
        CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
        CloudStorageErrorType::INVALID_FILE_TYPE,
        CloudStorageErrorType::FILE_TOO_LARGE,
        CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
        CloudStorageErrorType::PROVIDER_NOT_CONFIGURED,
    ];

    /**
     * Execute an operation with retry logic
     *
     * @param callable $operation The operation to execute
     * @param array $config Retry configuration
     * @param array $context Additional context for logging
     * @return mixed The result of the operation
     * @throws Exception If all retry attempts fail
     */
    public function executeWithRetry(callable $operation, array $config = [], array $context = [])
    {
        $config = array_merge(self::DEFAULT_CONFIG, $config);
        $attempt = 1;
        $lastException = null;

        Log::info('Starting operation with retry logic', [
            'max_attempts' => $config['max_attempts'],
            'context' => $context
        ]);

        while ($attempt <= $config['max_attempts']) {
            try {
                Log::debug("Executing operation attempt {$attempt}/{$config['max_attempts']}", $context);
                
                $result = $operation();
                
                if ($attempt > 1) {
                    Log::info("Operation succeeded on attempt {$attempt}", $context);
                }
                
                return $result;
                
            } catch (Exception $exception) {
                $lastException = $exception;
                
                Log::warning("Operation failed on attempt {$attempt}", [
                    'attempt' => $attempt,
                    'max_attempts' => $config['max_attempts'],
                    'error' => $exception->getMessage(),
                    'context' => $context
                ]);

                // Check if this error type is retryable
                if (!$this->isRetryableException($exception)) {
                    Log::info('Exception is not retryable, stopping retry attempts', [
                        'error' => $exception->getMessage(),
                        'context' => $context
                    ]);
                    throw $exception;
                }

                // If this was the last attempt, throw the exception
                if ($attempt >= $config['max_attempts']) {
                    Log::error('All retry attempts exhausted', [
                        'attempts' => $attempt,
                        'final_error' => $exception->getMessage(),
                        'context' => $context
                    ]);
                    break;
                }

                // Calculate delay for next attempt
                $delay = $this->calculateDelay($attempt, $config);
                
                Log::debug("Waiting {$delay}ms before retry attempt", [
                    'attempt' => $attempt,
                    'delay_ms' => $delay,
                    'context' => $context
                ]);

                // Wait before retrying (convert to microseconds for usleep)
                usleep($delay * 1000);
                
                $attempt++;
            }
        }

        // If we get here, all attempts failed
        throw $lastException;
    }

    /**
     * Check if an exception is retryable
     *
     * @param Exception $exception The exception to check
     * @return bool Whether the exception is retryable
     */
    public function isRetryableException(Exception $exception): bool
    {
        // Check if it's a Google Service Exception
        if ($exception instanceof \Google\Service\Exception) {
            return $this->isRetryableGoogleException($exception);
        }

        // Check for network-related exceptions
        if ($this->isNetworkException($exception)) {
            return true;
        }

        // Check for timeout exceptions
        if ($this->isTimeoutException($exception)) {
            return true;
        }

        // Check for service unavailable exceptions
        if ($this->isServiceUnavailableException($exception)) {
            return true;
        }

        return false;
    }

    /**
     * Check if a Google Service Exception is retryable
     *
     * @param \Google\Service\Exception $exception
     * @return bool
     */
    private function isRetryableGoogleException(\Google\Service\Exception $exception): bool
    {
        $code = $exception->getCode();
        $errors = $exception->getErrors();
        $reason = $errors[0]['reason'] ?? null;

        // Retryable HTTP status codes
        $retryableCodes = [429, 500, 502, 503, 504];
        
        if (in_array($code, $retryableCodes)) {
            return true;
        }

        // Retryable reasons
        $retryableReasons = [
            'rateLimitExceeded',
            'userRateLimitExceeded',
            'quotaExceeded',
            'backendError',
            'internalError',
            'serviceUnavailable'
        ];

        return in_array($reason, $retryableReasons);
    }

    /**
     * Check if an exception is network-related
     *
     * @param Exception $exception
     * @return bool
     */
    private function isNetworkException(Exception $exception): bool
    {
        $message = strtolower($exception->getMessage());
        
        $networkKeywords = [
            'connection',
            'network',
            'timeout',
            'unreachable',
            'dns',
            'socket',
            'curl',
            'ssl',
            'certificate'
        ];

        foreach ($networkKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an exception is timeout-related
     *
     * @param Exception $exception
     * @return bool
     */
    private function isTimeoutException(Exception $exception): bool
    {
        $message = strtolower($exception->getMessage());
        
        return str_contains($message, 'timeout') || 
               str_contains($message, 'timed out') ||
               str_contains($message, 'time limit exceeded');
    }

    /**
     * Check if an exception indicates service unavailability
     *
     * @param Exception $exception
     * @return bool
     */
    private function isServiceUnavailableException(Exception $exception): bool
    {
        $message = strtolower($exception->getMessage());
        
        return str_contains($message, 'service unavailable') ||
               str_contains($message, 'server error') ||
               str_contains($message, 'internal server error') ||
               str_contains($message, 'bad gateway') ||
               str_contains($message, 'gateway timeout');
    }

    /**
     * Calculate delay for next retry attempt
     *
     * @param int $attempt Current attempt number (1-based)
     * @param array $config Retry configuration
     * @return int Delay in milliseconds
     */
    private function calculateDelay(int $attempt, array $config): int
    {
        // Calculate exponential backoff delay
        $delay = $config['base_delay'] * pow($config['backoff_multiplier'], $attempt - 1);
        
        // Apply maximum delay limit
        $delay = min($delay, $config['max_delay']);
        
        // Add jitter to prevent thundering herd
        if ($config['jitter']) {
            $jitterRange = $delay * 0.1; // 10% jitter
            $jitter = mt_rand(-$jitterRange, $jitterRange);
            $delay += $jitter;
        }
        
        return max(0, (int) $delay);
    }

    /**
     * Get retry configuration for a specific error type
     *
     * @param CloudStorageErrorType $errorType
     * @return array Retry configuration
     */
    public function getRetryConfigForErrorType(CloudStorageErrorType $errorType): array
    {
        return match ($errorType) {
            CloudStorageErrorType::API_QUOTA_EXCEEDED => [
                'max_attempts' => 2,
                'base_delay' => 60000, // 1 minute
                'max_delay' => 300000, // 5 minutes
                'backoff_multiplier' => 2,
                'jitter' => true,
            ],
            CloudStorageErrorType::NETWORK_ERROR => [
                'max_attempts' => 3,
                'base_delay' => 2000, // 2 seconds
                'max_delay' => 30000, // 30 seconds
                'backoff_multiplier' => 2,
                'jitter' => true,
            ],
            CloudStorageErrorType::SERVICE_UNAVAILABLE => [
                'max_attempts' => 3,
                'base_delay' => 5000, // 5 seconds
                'max_delay' => 60000, // 1 minute
                'backoff_multiplier' => 2,
                'jitter' => true,
            ],
            CloudStorageErrorType::TIMEOUT => [
                'max_attempts' => 2,
                'base_delay' => 3000, // 3 seconds
                'max_delay' => 15000, // 15 seconds
                'backoff_multiplier' => 2,
                'jitter' => true,
            ],
            default => self::DEFAULT_CONFIG
        };
    }

    /**
     * Check if an error type is retryable
     *
     * @param CloudStorageErrorType $errorType
     * @return bool
     */
    public function isRetryableErrorType(CloudStorageErrorType $errorType): bool
    {
        return in_array($errorType, self::RETRYABLE_ERRORS);
    }

    /**
     * Get human-readable retry information
     *
     * @param int $attempt Current attempt number
     * @param int $maxAttempts Maximum attempts
     * @param int $delayMs Delay in milliseconds
     * @return array Retry information
     */
    public function getRetryInfo(int $attempt, int $maxAttempts, int $delayMs): array
    {
        return [
            'attempt' => $attempt,
            'max_attempts' => $maxAttempts,
            'remaining_attempts' => $maxAttempts - $attempt,
            'delay_seconds' => round($delayMs / 1000, 1),
            'is_final_attempt' => $attempt >= $maxAttempts,
            'retry_message' => $attempt < $maxAttempts ? 
                "Retrying in " . round($delayMs / 1000, 1) . " seconds... (Attempt {$attempt}/{$maxAttempts})" :
                "Final attempt failed. No more retries."
        ];
    }
}