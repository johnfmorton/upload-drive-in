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
    public static function testing(?string $testJobId, string $message = 'Testing queue worker...'): self
    {
        return new self(
            status: self::STATUS_TESTING,
            message: $message,
            testJobId: $testJobId,
            canRetry: false
        );
    }

    /**
     * Create a status for when test job is queued.
     */
    public static function queued(string $testJobId): self
    {
        return new self(
            status: self::STATUS_TESTING,
            message: 'Test job queued, waiting for worker...',
            testJobId: $testJobId,
            canRetry: false
        );
    }

    /**
     * Create a status for when test job is processing.
     */
    public static function processing(string $testJobId): self
    {
        return new self(
            status: self::STATUS_TESTING,
            message: 'Test job processing...',
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
     * Create a status for dispatch failures.
     */
    public static function dispatchFailed(string $errorMessage, ?array $troubleshooting = null): self
    {
        return new self(
            status: self::STATUS_FAILED,
            message: 'Failed to dispatch test job',
            errorMessage: $errorMessage,
            troubleshooting: $troubleshooting ?? self::getDispatchFailureTroubleshooting(),
            canRetry: true
        );
    }

    /**
     * Create a status for job execution failures.
     */
    public static function jobFailed(string $errorMessage, string $testJobId, ?array $troubleshooting = null): self
    {
        return new self(
            status: self::STATUS_FAILED,
            message: 'Test job execution failed',
            errorMessage: $errorMessage,
            testJobId: $testJobId,
            troubleshooting: $troubleshooting ?? self::getJobFailureTroubleshooting(),
            canRetry: true
        );
    }

    /**
     * Create a status for network/communication errors.
     */
    public static function networkError(string $errorMessage, ?string $testJobId = null): self
    {
        return new self(
            status: self::STATUS_ERROR,
            message: 'Network error during queue test',
            errorMessage: $errorMessage,
            testJobId: $testJobId,
            troubleshooting: self::getNetworkErrorTroubleshooting(),
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
     * Create a status for configuration errors.
     */
    public static function configurationError(string $errorMessage, ?array $troubleshooting = null): self
    {
        return new self(
            status: self::STATUS_FAILED,
            message: 'Queue configuration error',
            errorMessage: $errorMessage,
            troubleshooting: $troubleshooting ?? self::getConfigurationErrorTroubleshooting(),
            canRetry: true
        );
    }

    /**
     * Create a status for database connection errors.
     */
    public static function databaseError(string $errorMessage, ?array $troubleshooting = null): self
    {
        return new self(
            status: self::STATUS_FAILED,
            message: 'Database connection error',
            errorMessage: $errorMessage,
            troubleshooting: $troubleshooting ?? self::getDatabaseErrorTroubleshooting(),
            canRetry: true
        );
    }

    /**
     * Create a status for permission errors.
     */
    public static function permissionError(string $errorMessage, ?array $troubleshooting = null): self
    {
        return new self(
            status: self::STATUS_FAILED,
            message: 'Permission error',
            errorMessage: $errorMessage,
            troubleshooting: $troubleshooting ?? self::getPermissionErrorTroubleshooting(),
            canRetry: true
        );
    }

    /**
     * Create a status for worker not running scenario.
     */
    public static function workerNotRunning(?string $testJobId = null): self
    {
        return new self(
            status: self::STATUS_TIMEOUT,
            message: 'Queue worker is not running',
            errorMessage: 'Test job timed out - no worker process detected',
            testJobId: $testJobId,
            troubleshooting: self::getWorkerNotRunningTroubleshooting(),
            canRetry: true
        );
    }

    /**
     * Create a status for worker stuck scenario.
     */
    public static function workerStuck(?string $testJobId = null): self
    {
        return new self(
            status: self::STATUS_TIMEOUT,
            message: 'Queue worker appears stuck',
            errorMessage: 'Test job timed out - worker may be processing other jobs or stuck',
            testJobId: $testJobId,
            troubleshooting: self::getWorkerStuckTroubleshooting(),
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

    /**
     * Get troubleshooting steps for dispatch failures.
     */
    private static function getDispatchFailureTroubleshooting(): array
    {
        return [
            'Verify queue configuration in .env file (QUEUE_CONNECTION)',
            'Check if database tables exist: php artisan migrate',
            'Ensure queue driver is properly configured (database, redis, etc.)',
            'Check application logs for configuration errors',
            'Verify file permissions for storage and cache directories',
            'Test database connection: php artisan tinker, then DB::connection()->getPdo()',
            'For Redis queue: ensure Redis server is running and accessible',
        ];
    }

    /**
     * Get troubleshooting steps for job execution failures.
     */
    private static function getJobFailureTroubleshooting(): array
    {
        return [
            'Check failed jobs table: php artisan queue:failed',
            'Review worker logs for specific error details',
            'Ensure all required dependencies are installed',
            'Check memory limits and execution time settings',
            'Verify database connectivity from worker process',
            'Restart queue worker: php artisan queue:restart',
            'Clear application cache: php artisan cache:clear',
            'Check disk space and file permissions',
        ];
    }

    /**
     * Get troubleshooting steps for network errors.
     */
    private static function getNetworkErrorTroubleshooting(): array
    {
        return [
            'Check your internet connection',
            'Verify the application server is accessible',
            'Check for firewall or proxy issues',
            'Try refreshing the page and testing again',
            'Contact your network administrator if issues persist',
        ];
    }

    /**
     * Get troubleshooting steps for configuration errors.
     */
    private static function getConfigurationErrorTroubleshooting(): array
    {
        return [
            'Check QUEUE_CONNECTION setting in .env file',
            'Verify queue driver configuration matches your setup',
            'For database queue: ensure migrations are run (php artisan migrate)',
            'For Redis queue: verify REDIS_HOST and REDIS_PORT settings',
            'Check if required PHP extensions are installed (redis, pdo_mysql, etc.)',
            'Review config/queue.php for correct driver configuration',
            'Clear configuration cache: php artisan config:clear',
        ];
    }

    /**
     * Get troubleshooting steps for database errors.
     */
    private static function getDatabaseErrorTroubleshooting(): array
    {
        return [
            'Verify database connection settings in .env file',
            'Check if database server is running and accessible',
            'Ensure database user has proper permissions',
            'Test database connection: php artisan tinker, then DB::connection()->getPdo()',
            'Check if jobs and failed_jobs tables exist: php artisan migrate',
            'Verify database disk space and memory limits',
            'Review database server logs for connection errors',
        ];
    }

    /**
     * Get troubleshooting steps for permission errors.
     */
    private static function getPermissionErrorTroubleshooting(): array
    {
        return [
            'Check file permissions on storage directory: chmod -R 755 storage',
            'Ensure web server user owns storage directory: chown -R www-data:www-data storage',
            'Verify bootstrap/cache directory permissions: chmod -R 755 bootstrap/cache',
            'Check if SELinux is blocking file access (if applicable)',
            'Ensure queue worker process has write access to log files',
            'Verify .env file is readable by the application',
            'Check parent directory permissions for storage and cache paths',
        ];
    }

    /**
     * Get troubleshooting steps for worker not running scenario.
     */
    private static function getWorkerNotRunningTroubleshooting(): array
    {
        return [
            'Start the queue worker: php artisan queue:work',
            'Check if worker process is running: ps aux | grep "queue:work"',
            'For production: ensure process manager (Supervisor/systemd) is configured',
            'Verify queue worker service is enabled and started',
            'Check system resources (CPU, memory) for worker process',
            'Review worker startup logs for initialization errors',
            'Test manual job dispatch: php artisan queue:work --once',
            'Ensure no firewall blocking internal queue communication',
        ];
    }

    /**
     * Get troubleshooting steps for worker stuck scenario.
     */
    private static function getWorkerStuckTroubleshooting(): array
    {
        return [
            'Restart the queue worker: php artisan queue:restart',
            'Check worker memory usage: ps aux | grep "queue:work"',
            'Review worker logs for errors or infinite loops',
            'Check for long-running jobs blocking the queue',
            'Verify database connections are not exhausted',
            'Increase worker timeout settings if processing large jobs',
            'Consider using multiple worker processes for better throughput',
            'Check for deadlocks in failed_jobs table',
            'Monitor system resources during job processing',
        ];
    }
}