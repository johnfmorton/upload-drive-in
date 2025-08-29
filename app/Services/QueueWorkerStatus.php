<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Data class for queue worker status information.
 * 
 * This class represents the status of queue worker tests and provides
 * constants for different status states along with helper methods.
 */
class QueueWorkerStatus
{
    /**
     * Status constants for queue worker test states.
     */
    public const STATUS_NOT_TESTED = 'not_tested';
    public const STATUS_TESTING = 'testing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_TIMEOUT = 'timeout';
    public const STATUS_ERROR = 'error';

    /**
     * Cache TTL for queue worker status (1 hour in seconds).
     */
    public const CACHE_TTL = 3600;

    /**
     * Cache key for queue worker status.
     */
    public const CACHE_KEY = 'setup_queue_worker_status';

    public string $status;
    public string $message;
    public ?Carbon $testCompletedAt;
    public ?float $processingTime;
    public ?string $errorMessage;
    public ?string $testJobId;
    public ?array $troubleshooting;
    public bool $canRetry;

    /**
     * Create a new QueueWorkerStatus instance.
     */
    public function __construct(
        string $status = self::STATUS_NOT_TESTED,
        string $message = '',
        ?Carbon $testCompletedAt = null,
        ?float $processingTime = null,
        ?string $errorMessage = null,
        ?string $testJobId = null,
        ?array $troubleshooting = null,
        bool $canRetry = true
    ) {
        $this->status = $status;
        $this->message = $message;
        $this->testCompletedAt = $testCompletedAt;
        $this->processingTime = $processingTime;
        $this->errorMessage = $errorMessage;
        $this->testJobId = $testJobId;
        $this->troubleshooting = $troubleshooting;
        $this->canRetry = $canRetry;
    }

    /**
     * Create a status for when no test has been performed.
     */
    public static function notTested(): self
    {
        return new self(
            status: self::STATUS_NOT_TESTED,
            message: 'Click the Test Queue Worker button below',
            canRetry: true
        );
    }

    /**
     * Create a status for when testing is in progress.
     */
    public static function testing(string $testJobId, string $message = 'Testing queue worker...'): self
    {
        return new self(
            status: self::STATUS_TESTING,
            message: $message,
            testJobId: $testJobId,
            canRetry: false
        );
    }

    /**
     * Create a status for successful test completion.
     */
    public static function completed(float $processingTime, string $testJobId): self
    {
        return new self(
            status: self::STATUS_COMPLETED,
            message: sprintf('Queue worker is functioning properly (%.2fs)', $processingTime),
            testCompletedAt: Carbon::now(),
            processingTime: $processingTime,
            testJobId: $testJobId,
            canRetry: true
        );
    }

    /**
     * Create a status for failed tests.
     */
    public static function failed(string $errorMessage, ?string $testJobId = null, ?array $troubleshooting = null): self
    {
        return new self(
            status: self::STATUS_FAILED,
            message: 'Queue worker test failed',
            errorMessage: $errorMessage,
            testJobId: $testJobId,
            troubleshooting: $troubleshooting ?? self::getDefaultTroubleshooting(),
            canRetry: true
        );
    }

    /**
     * Create a status for timed out tests.
     */
    public static function timeout(?string $testJobId = null): self
    {
        return new self(
            status: self::STATUS_TIMEOUT,
            message: 'Queue worker test timed out - worker may not be running',
            errorMessage: 'Test job did not complete within the expected time',
            testJobId: $testJobId,
            troubleshooting: self::getTimeoutTroubleshooting(),
            canRetry: true
        );
    }

    /**
     * Create a status for system errors.
     */
    public static function error(string $errorMessage, ?string $testJobId = null): self
    {
        return new self(
            status: self::STATUS_ERROR,
            message: 'Error checking queue worker status',
            errorMessage: $errorMessage,
            testJobId: $testJobId,
            troubleshooting: self::getErrorTroubleshooting(),
            canRetry: true
        );
    }

    /**
     * Check if the status is expired based on the test completion time.
     * Only completed statuses can expire based on time.
     * Other statuses are considered valid until explicitly cleared.
     */
    public function isExpired(): bool
    {
        // Only completed statuses expire based on time
        if ($this->status === self::STATUS_COMPLETED && $this->testCompletedAt) {
            return $this->testCompletedAt->addSeconds(self::CACHE_TTL)->isPast();
        }
        
        // Not tested status is always considered expired (needs testing)
        if ($this->status === self::STATUS_NOT_TESTED) {
            return true;
        }
        
        // Other statuses (testing, failed, timeout, error) don't expire automatically
        return false;
    }

    /**
     * Check if the status indicates success.
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the status indicates failure.
     */
    public function isFailed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_TIMEOUT, self::STATUS_ERROR]);
    }

    /**
     * Check if testing is currently in progress.
     */
    public function isTesting(): bool
    {
        return $this->status === self::STATUS_TESTING;
    }

    /**
     * Convert to array for API responses.
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'test_completed_at' => $this->testCompletedAt?->toISOString(),
            'processing_time' => $this->processingTime,
            'error_message' => $this->errorMessage,
            'test_job_id' => $this->testJobId,
            'troubleshooting' => $this->troubleshooting,
            'can_retry' => $this->canRetry,
            'is_expired' => $this->isExpired(),
        ];
    }

    /**
     * Create from array data (for cache deserialization).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'] ?? self::STATUS_NOT_TESTED,
            message: $data['message'] ?? '',
            testCompletedAt: isset($data['test_completed_at']) ? Carbon::parse($data['test_completed_at']) : null,
            processingTime: $data['processing_time'] ?? null,
            errorMessage: $data['error_message'] ?? null,
            testJobId: $data['test_job_id'] ?? null,
            troubleshooting: $data['troubleshooting'] ?? null,
            canRetry: $data['can_retry'] ?? true
        );
    }

    /**
     * Get default troubleshooting steps for failed tests.
     */
    private static function getDefaultTroubleshooting(): array
    {
        return [
            'Check if queue worker is running: php artisan queue:work',
            'Verify queue configuration in .env file',
            'Check for failed jobs: php artisan queue:failed',
            'Review application logs for errors',
        ];
    }

    /**
     * Get troubleshooting steps for timeout scenarios.
     */
    private static function getTimeoutTroubleshooting(): array
    {
        return [
            'Ensure queue worker is running: php artisan queue:work',
            'Check if worker process is stuck or crashed',
            'Verify queue driver configuration (database, redis, etc.)',
            'Check system resources (CPU, memory, disk space)',
            'Review worker logs for errors or warnings',
        ];
    }

    /**
     * Get troubleshooting steps for system errors.
     */
    private static function getErrorTroubleshooting(): array
    {
        return [
            'Check application logs for detailed error information',
            'Verify cache service is running and accessible',
            'Try refreshing the page and testing again',
            'Contact administrator if problem persists',
        ];
    }
}