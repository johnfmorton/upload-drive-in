<?php

namespace App\Contracts;

use App\Enums\CloudStorageErrorType;
use Exception;

/**
 * Interface for cloud storage error handling
 * 
 * Provides universal error handling across all cloud storage providers
 * with error classification, user-friendly messages, and retry logic
 */
interface CloudStorageErrorHandlerInterface
{
    /**
     * Classify an exception into a universal error type
     *
     * @param Exception $exception The exception to classify
     * @return CloudStorageErrorType The classified error type
     */
    public function classifyError(Exception $exception): CloudStorageErrorType;

    /**
     * Generate a user-friendly error message for the given error type
     *
     * @param CloudStorageErrorType $type The error type
     * @param array $context Additional context for message generation
     * @return string User-friendly error message
     */
    public function getUserFriendlyMessage(CloudStorageErrorType $type, array $context = []): string;

    /**
     * Determine if an error should be retried based on type and attempt count
     *
     * @param CloudStorageErrorType $type The error type
     * @param int $attemptCount Current attempt count (1-based)
     * @return bool True if the operation should be retried
     */
    public function shouldRetry(CloudStorageErrorType $type, int $attemptCount): bool;

    /**
     * Get the delay in seconds before retrying an operation
     *
     * @param CloudStorageErrorType $type The error type
     * @param int $attemptCount Current attempt count (1-based)
     * @return int Delay in seconds before retry
     */
    public function getRetryDelay(CloudStorageErrorType $type, int $attemptCount): int;

    /**
     * Get the maximum number of retry attempts for an error type
     *
     * @param CloudStorageErrorType $type The error type
     * @return int Maximum retry attempts
     */
    public function getMaxRetryAttempts(CloudStorageErrorType $type): int;

    /**
     * Determine if an error requires user intervention
     *
     * @param CloudStorageErrorType $type The error type
     * @return bool True if user intervention is required
     */
    public function requiresUserIntervention(CloudStorageErrorType $type): bool;

    /**
     * Get recommended actions for the user to resolve the error
     *
     * @param CloudStorageErrorType $type The error type
     * @param array $context Additional context for recommendations
     * @return array Array of recommended actions
     */
    public function getRecommendedActions(CloudStorageErrorType $type, array $context = []): array;
}