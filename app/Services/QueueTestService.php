<?php

namespace App\Services;

use App\Jobs\TestQueueJob;
use App\Services\QueueWorkerStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Throwable;
use PDOException;
use Illuminate\Database\QueryException;

/**
 * Service for testing queue functionality and monitoring queue health.
 * 
 * This service provides methods to dispatch test jobs, track their progress,
 * monitor queue health metrics, and clean up old test data.
 */
class QueueTestService
{
    private QueueWorkerTestSecurityService $securityService;
    private QueueWorkerPerformanceService $performanceService;

    public function __construct(
        QueueWorkerTestSecurityService $securityService,
        QueueWorkerPerformanceService $performanceService
    ) {
        $this->securityService = $securityService;
        $this->performanceService = $performanceService;
    }
    /**
     * Cache key prefix for test job tracking.
     */
    private const CACHE_PREFIX = 'test_queue_job_';

    /**
     * Cache key for storing test job index.
     */
    private const INDEX_CACHE_KEY = 'test_queue_job_index';

    /**
     * Default timeout for test jobs in seconds.
     */
    private const DEFAULT_TIMEOUT = 30;

    /**
     * Configurable timeout periods for different scenarios.
     */
    private const TIMEOUT_DISPATCH = 10;      // Timeout for job dispatch
    private const TIMEOUT_QUICK_TEST = 15;    // Timeout for quick tests
    private const TIMEOUT_STANDARD = 30;      // Standard timeout
    private const TIMEOUT_EXTENDED = 60;      // Extended timeout for slow systems

    /**
     * TTL for test job cache entries in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Maximum retry attempts for failed operations.
     */
    private const MAX_RETRY_ATTEMPTS = 3;

    /**
     * Retry delay in milliseconds.
     */
    private const RETRY_DELAY_MS = 1000;

    /**
     * Timeout for database operations in seconds.
     */
    private const DB_TIMEOUT = 10;

    /**
     * Dispatch a test job and return unique job ID for tracking.
     * 
     * @param int $delay Optional delay in seconds before job processing
     * @param int $retryAttempt Current retry attempt (internal use)
     * @return string Unique job ID for tracking
     * @throws Exception If job dispatch fails after all retries
     */
    public function dispatchTestJob(int $delay = 0, int $retryAttempt = 0): string
    {
        $maxAttempts = self::MAX_RETRY_ATTEMPTS;
        $lastException = null;

        for ($attempt = $retryAttempt; $attempt < $maxAttempts; $attempt++) {
            try {
                if ($attempt > 0) {
                    Log::debug('Retrying test job dispatch', [
                        'attempt' => $attempt + 1,
                        'max_attempts' => $maxAttempts,
                        'delay' => $delay
                    ]);

                    // Add delay between retries
                    usleep(self::RETRY_DELAY_MS * 1000);
                }

                // Generate unique job ID
                $jobId = $this->generateUniqueJobId();

                // Initialize job status in cache with timeout handling
                $this->initializeJobStatusWithTimeout($jobId, $delay);

                // Add job ID to index for cleanup tracking
                $this->addJobToIndexWithRetry($jobId);

                // Dispatch the test job with error handling
                $this->dispatchJobWithTimeout($jobId, $delay);

                Log::info('Test queue job dispatched successfully', [
                    'test_job_id' => $jobId,
                    'delay' => $delay,
                    'attempt' => $attempt + 1,
                    'dispatched_at' => Carbon::now()->toISOString(),
                ]);

                return $jobId;
            } catch (Exception $e) {
                $lastException = $e;

                Log::warning('Test job dispatch failed, will retry if attempts remain', [
                    'attempt' => $attempt + 1,
                    'max_attempts' => $maxAttempts,
                    'delay' => $delay,
                    'error' => $e->getMessage(),
                    'remaining_attempts' => $maxAttempts - $attempt - 1
                ]);

                // Don't retry certain types of errors
                if ($this->shouldNotRetryDispatch($e)) {
                    Log::info('Dispatch error should not be retried', [
                        'error_type' => get_class($e),
                        'error_message' => $e->getMessage()
                    ]);
                    break;
                }
            } catch (Throwable $e) {
                $lastException = $e;

                Log::critical('Critical error during test job dispatch', [
                    'attempt' => $attempt + 1,
                    'delay' => $delay,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                break;
            }
        }

        // All attempts failed
        Log::error('Failed to dispatch test queue job after all retries', [
            'max_attempts' => $maxAttempts,
            'delay' => $delay,
            'final_error' => $lastException ? $lastException->getMessage() : 'Unknown error'
        ]);

        throw new Exception(
            'Failed to dispatch test job after ' . $maxAttempts . ' attempts: ' .
                ($lastException ? $lastException->getMessage() : 'Unknown error'),
            0,
            $lastException
        );
    }

    /**
     * Check the status of a test job by job ID.
     * 
     * @param string $jobId The unique job ID to check
     * @return array Job status information
     */
    public function checkTestJobStatus(string $jobId): array
    {
        try {
            // Validate job ID format
            if (!$this->isValidJobId($jobId)) {
                Log::warning('Invalid job ID format provided', [
                    'job_id' => $jobId
                ]);

                return [
                    'test_job_id' => $jobId,
                    'status' => 'invalid',
                    'message' => 'Invalid job ID format',
                    'error' => 'Job ID must match pattern: test_[uuid]',
                    'checked_at' => Carbon::now()->toISOString(),
                ];
            }

            $cacheKey = self::CACHE_PREFIX . $jobId;

            // Execute with timeout to prevent hanging
            $status = $this->executeWithTimeout(function () use ($cacheKey) {
                return Cache::get($cacheKey);
            }, 5); // 5 second timeout for cache operations

            if (!$status) {
                Log::debug('Test job not found in cache', [
                    'test_job_id' => $jobId,
                    'cache_key' => $cacheKey
                ]);

                return [
                    'test_job_id' => $jobId,
                    'status' => 'not_found',
                    'message' => 'Test job not found or expired',
                    'details' => [
                        'cache_key' => $cacheKey,
                        'possible_reasons' => [
                            'Job ID does not exist',
                            'Job data has expired from cache',
                            'Cache service is unavailable'
                        ]
                    ],
                    'checked_at' => Carbon::now()->toISOString(),
                ];
            }

            // Check for timeout if job is still pending or processing
            if (in_array($status['status'], ['pending', 'processing'])) {
                $status = $this->checkForTimeoutWithFallback($jobId, $status);
            }

            // Add additional metadata
            $status['checked_at'] = Carbon::now()->toISOString();
            $status['cache_hit'] = true;

            return $status;
        } catch (Exception $e) {
            Log::error('Failed to check test job status', [
                'test_job_id' => $jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->getErrorJobStatus($jobId, $e);
        } catch (Throwable $e) {
            Log::critical('Critical error checking test job status', [
                'test_job_id' => $jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->getErrorJobStatus($jobId, $e);
        }
    }

    /**
     * Get comprehensive queue health metrics for monitoring.
     * 
     * @return array Queue health metrics and statistics
     */
    public function getQueueHealthMetrics(): array
    {
        try {
            $now = Carbon::now();
            $last24Hours = $now->copy()->subHours(24);
            $lastHour = $now->copy()->subHour();

            // Get basic queue statistics
            $metrics = [
                'timestamp' => $now->toISOString(),
                'queue_tables_exist' => $this->checkQueueTablesExist(),
            ];

            if (!$metrics['queue_tables_exist']) {
                $metrics['status'] = 'error';
                $metrics['message'] = 'Queue tables not found - migrations may not be complete';
                return $metrics;
            }

            // Job statistics
            $metrics['job_statistics'] = [
                'pending_jobs' => DB::table('jobs')->count(),
                'failed_jobs_total' => DB::table('failed_jobs')->count(),
                'failed_jobs_24h' => DB::table('failed_jobs')
                    ->where('failed_at', '>=', $last24Hours)
                    ->count(),
                'failed_jobs_1h' => DB::table('failed_jobs')
                    ->where('failed_at', '>=', $lastHour)
                    ->count(),
            ];

            // Check for stalled jobs
            $metrics['stalled_jobs'] = DB::table('jobs')
                ->whereNotNull('reserved_at')
                ->where('reserved_at', '<', $now->subHour()->timestamp)
                ->count();

            // Test job statistics
            $metrics['test_job_statistics'] = $this->getTestJobStatistics();

            // Recent failed job details (for admin context)
            $metrics['recent_failed_jobs'] = $this->getRecentFailedJobDetails($last24Hours);

            // Overall health assessment
            $metrics = array_merge($metrics, $this->assessQueueHealth($metrics));

            return $metrics;
        } catch (Exception $e) {
            Log::error('Failed to get queue health metrics', [
                'error' => $e->getMessage(),
            ]);

            return [
                'timestamp' => Carbon::now()->toISOString(),
                'status' => 'error',
                'message' => 'Error retrieving queue health metrics',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get cached queue worker status or return default not tested status.
     * 
     * @return QueueWorkerStatus The current queue worker status
     */
    public function getCachedQueueWorkerStatus(): QueueWorkerStatus
    {
        try {
            // Use performance service for optimized cache retrieval
            $cachedData = $this->performanceService->getCachedData(QueueWorkerStatus::CACHE_KEY);

            if (!$cachedData) {
                Log::debug('No cached queue worker status found');
                return QueueWorkerStatus::notTested();
            }

            // Security validation of cached data
            $validatedData = $this->securityService->validateCachedStatus($cachedData);
            if (!$validatedData) {
                Log::warning('Cached queue worker status failed security validation, clearing cache', [
                    'cached_data_type' => gettype($cachedData)
                ]);
                $this->invalidateQueueWorkerStatus();
                return QueueWorkerStatus::notTested();
            }

            $status = QueueWorkerStatus::fromArray($validatedData);

            // Check if the cached status is expired (only for completed statuses)
            if ($status->status === QueueWorkerStatus::STATUS_COMPLETED && $status->isExpired()) {
                Log::debug('Cached queue worker status is expired', [
                    'test_completed_at' => $status->testCompletedAt?->toISOString(),
                    'cache_ttl' => QueueWorkerStatus::CACHE_TTL
                ]);

                // Clear expired cache and return not tested status
                $this->invalidateQueueWorkerStatus();
                return QueueWorkerStatus::notTested();
            }

            Log::debug('Retrieved valid cached queue worker status', [
                'status' => $status->status,
                'test_completed_at' => $status->testCompletedAt?->toISOString()
            ]);

            return $status;
        } catch (Exception $e) {
            Log::error('Failed to retrieve cached queue worker status', [
                'error' => $e->getMessage()
            ]);

            return QueueWorkerStatus::error('Failed to retrieve cached status: ' . $e->getMessage());
        }
    }

    /**
     * Cache queue worker status with appropriate TTL using performance service.
     * 
     * @param QueueWorkerStatus $status The status to cache
     * @return bool True if caching was successful
     */
    public function cacheQueueWorkerStatus(QueueWorkerStatus $status): bool
    {
        try {
            // Validate and sanitize the status data before caching
            $statusArray = $status->toArray();
            $validatedData = $this->securityService->validateStatusUpdate($statusArray);

            // Use performance service for optimized caching
            $success = $this->performanceService->cacheWithOptimizedTTL(
                QueueWorkerStatus::CACHE_KEY,
                $validatedData,
                $status->status
            );

            if ($success) {
                Log::debug('Queue worker status cached successfully with optimized TTL', [
                    'status' => $status->status,
                    'cache_key' => QueueWorkerStatus::CACHE_KEY
                ]);
            } else {
                Log::warning('Failed to cache queue worker status');
            }

            return $success;
        } catch (Exception $e) {
            Log::error('Error caching queue worker status', [
                'error' => $e->getMessage(),
                'status' => $status->status
            ]);

            return false;
        }
    }

    /**
     * Invalidate cached queue worker status.
     * 
     * @return bool True if invalidation was successful
     */
    public function invalidateQueueWorkerStatus(): bool
    {
        try {
            $success = Cache::forget(QueueWorkerStatus::CACHE_KEY);

            Log::debug('Queue worker status cache invalidated', [
                'success' => $success
            ]);

            return $success;
        } catch (Exception $e) {
            Log::error('Error invalidating queue worker status cache', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Dispatch a test job and cache the testing status with progressive updates.
     * 
     * @param int $delay Optional delay in seconds before job processing
     * @param int $timeout Optional timeout in seconds (uses default if not specified)
     * @return QueueWorkerStatus The testing status
     */
    public function dispatchTestJobWithStatus(int $delay = 0, int $timeout = null): QueueWorkerStatus
    {
        $timeout = $timeout ?? self::DEFAULT_TIMEOUT;

        try {
            // Phase 1: Initial testing status
            $initialStatus = QueueWorkerStatus::testing(null, 'Testing queue worker...');
            $this->cacheQueueWorkerStatus($initialStatus);

            // Phase 2: Dispatch the test job with enhanced error handling
            $jobId = $this->dispatchTestJobWithEnhancedErrorHandling($delay, $timeout);

            // Phase 3: Job dispatched, waiting for processing
            $status = QueueWorkerStatus::testing($jobId, 'Test job queued...');
            $this->cacheQueueWorkerStatus($status);

            Log::info('Queue worker test initiated with progressive status updates', [
                'test_job_id' => $jobId,
                'delay' => $delay,
                'timeout' => $timeout
            ]);

            return $status;
        } catch (Exception $e) {
            Log::error('Failed to dispatch test job with status', [
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'delay' => $delay,
                'timeout' => $timeout
            ]);

            // Determine specific error type and create appropriate status
            $status = $this->createErrorStatusFromException($e);
            $this->cacheQueueWorkerStatus($status);

            return $status;
        }
    }

    /**
     * Dispatch test job with enhanced error handling and specific error types.
     * 
     * @param int $delay Optional delay in seconds before job processing
     * @param int $timeout Timeout in seconds
     * @return string Unique job ID for tracking
     * @throws Exception If job dispatch fails after all retries
     */
    private function dispatchTestJobWithEnhancedErrorHandling(int $delay, int $timeout): string
    {
        $maxAttempts = self::MAX_RETRY_ATTEMPTS;
        $lastException = null;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                if ($attempt > 0) {
                    Log::debug('Retrying test job dispatch with enhanced error handling', [
                        'attempt' => $attempt + 1,
                        'max_attempts' => $maxAttempts,
                        'delay' => $delay,
                        'timeout' => $timeout
                    ]);

                    // Add delay between retries
                    usleep(self::RETRY_DELAY_MS * 1000);
                }

                // Generate unique job ID
                $jobId = $this->generateUniqueJobId();

                // Initialize job status in cache with configurable timeout
                $this->initializeJobStatusWithConfigurableTimeout($jobId, $delay, $timeout);

                // Add job ID to index for cleanup tracking
                $this->addJobToIndexWithRetry($jobId);

                // Dispatch the test job with timeout handling
                $this->dispatchJobWithTimeout($jobId, $delay);

                Log::info('Test queue job dispatched successfully with enhanced error handling', [
                    'test_job_id' => $jobId,
                    'delay' => $delay,
                    'timeout' => $timeout,
                    'attempt' => $attempt + 1,
                    'dispatched_at' => Carbon::now()->toISOString(),
                ]);

                return $jobId;
            } catch (Exception $e) {
                $lastException = $e;

                Log::warning('Test job dispatch failed with enhanced error handling', [
                    'attempt' => $attempt + 1,
                    'max_attempts' => $maxAttempts,
                    'delay' => $delay,
                    'timeout' => $timeout,
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                    'remaining_attempts' => $maxAttempts - $attempt - 1
                ]);

                // Don't retry certain types of errors
                if ($this->shouldNotRetryDispatch($e)) {
                    Log::info('Dispatch error should not be retried (enhanced)', [
                        'error_type' => get_class($e),
                        'error_message' => $e->getMessage()
                    ]);
                    break;
                }
            } catch (Throwable $e) {
                $lastException = $e;

                Log::critical('Critical error during test job dispatch (enhanced)', [
                    'attempt' => $attempt + 1,
                    'delay' => $delay,
                    'timeout' => $timeout,
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                    'trace' => $e->getTraceAsString()
                ]);
                break;
            }
        }

        // All attempts failed - throw with enhanced error information
        $errorMessage = $this->buildEnhancedErrorMessage($lastException, $maxAttempts);

        Log::error('Failed to dispatch test queue job after all retries (enhanced)', [
            'max_attempts' => $maxAttempts,
            'delay' => $delay,
            'timeout' => $timeout,
            'final_error' => $lastException ? $lastException->getMessage() : 'Unknown error',
            'final_error_type' => $lastException ? get_class($lastException) : 'Unknown'
        ]);

        throw new Exception($errorMessage, 0, $lastException);
    }

    /**
     * Update queue worker status with progressive test phase messages.
     * 
     * @param string $jobId The test job ID
     * @param string $phase The current test phase (dispatching, queued, processing)
     * @return QueueWorkerStatus The updated status
     */
    public function updateQueueWorkerTestPhase(string $jobId, string $phase): QueueWorkerStatus
    {
        try {
            $message = match ($phase) {
                'dispatching' => 'Testing queue worker...',
                'queued' => 'Test job queued...',
                'processing' => 'Test job processing...',
                default => 'Testing queue worker...'
            };

            $status = QueueWorkerStatus::testing($jobId, $message);
            $this->cacheQueueWorkerStatus($status);

            Log::debug('Queue worker test phase updated', [
                'test_job_id' => $jobId,
                'phase' => $phase,
                'message' => $message
            ]);

            return $status;
        } catch (Exception $e) {
            Log::error('Failed to update queue worker test phase', [
                'test_job_id' => $jobId,
                'phase' => $phase,
                'error' => $e->getMessage()
            ]);

            $status = QueueWorkerStatus::error('Failed to update test phase: ' . $e->getMessage(), $jobId);
            $this->cacheQueueWorkerStatus($status);

            return $status;
        }
    }

    /**
     * Update queue worker status based on test job completion.
     * 
     * @param string $jobId The test job ID
     * @param bool $success Whether the job completed successfully
     * @param float|null $processingTime Processing time in seconds
     * @param string|null $errorMessage Error message if job failed
     * @return QueueWorkerStatus The updated status
     */
    public function updateQueueWorkerStatusFromJob(
        string $jobId,
        bool $success,
        ?float $processingTime = null,
        ?string $errorMessage = null
    ): QueueWorkerStatus {
        try {
            if ($success && $processingTime !== null) {
                $status = QueueWorkerStatus::completed($processingTime, $jobId);

                Log::info('Queue worker test completed successfully', [
                    'test_job_id' => $jobId,
                    'processing_time' => $processingTime
                ]);
            } else {
                $status = QueueWorkerStatus::failed(
                    $errorMessage ?? 'Test job failed without specific error',
                    $jobId
                );

                Log::warning('Queue worker test failed', [
                    'test_job_id' => $jobId,
                    'error_message' => $errorMessage
                ]);
            }

            $this->cacheQueueWorkerStatus($status);
            return $status;
        } catch (Exception $e) {
            Log::error('Failed to update queue worker status from job', [
                'test_job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            $status = QueueWorkerStatus::error('Failed to update status: ' . $e->getMessage(), $jobId);
            $this->cacheQueueWorkerStatus($status);

            return $status;
        }
    }

    /**
     * Check for timeout and update queue worker status if needed.
     * 
     * @param string $jobId The test job ID to check
     * @return QueueWorkerStatus The current or updated status
     */
    public function checkQueueWorkerTimeout(string $jobId): QueueWorkerStatus
    {
        try {
            $cachedStatus = $this->getCachedQueueWorkerStatus();

            // Only check timeout for testing status with matching job ID
            if (
                $cachedStatus->status !== QueueWorkerStatus::STATUS_TESTING ||
                $cachedStatus->testJobId !== $jobId
            ) {
                return $cachedStatus;
            }

            // Check the underlying test job status for timeout
            $jobStatus = $this->checkTestJobStatus($jobId);

            if ($jobStatus['status'] === 'timeout') {
                // Determine specific timeout type based on queue health
                $status = $this->determineTimeoutType($jobId);
                $this->cacheQueueWorkerStatus($status);

                Log::warning('Queue worker test timed out', [
                    'test_job_id' => $jobId,
                    'timeout_type' => $status->message
                ]);

                return $status;
            }

            return $cachedStatus;
        } catch (Exception $e) {
            Log::error('Failed to check queue worker timeout', [
                'test_job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            $status = QueueWorkerStatus::error('Failed to check timeout: ' . $e->getMessage(), $jobId);
            $this->cacheQueueWorkerStatus($status);

            return $status;
        }
    }

    /**
     * Determine the specific type of timeout based on queue health metrics.
     * 
     * @param string $jobId The test job ID
     * @return QueueWorkerStatus Specific timeout status
     */
    private function determineTimeoutType(string $jobId): QueueWorkerStatus
    {
        try {
            $healthMetrics = $this->getQueueHealthMetrics();

            // Check if there are pending jobs (worker might be stuck)
            if (
                isset($healthMetrics['job_statistics']['pending_jobs']) &&
                $healthMetrics['job_statistics']['pending_jobs'] > 0
            ) {
                return QueueWorkerStatus::workerStuck($jobId);
            }

            // Check for recent failed jobs (worker might be crashing)
            if (
                isset($healthMetrics['job_statistics']['failed_jobs_1h']) &&
                $healthMetrics['job_statistics']['failed_jobs_1h'] > 0
            ) {
                return QueueWorkerStatus::workerStuck($jobId);
            }

            // Default to worker not running
            return QueueWorkerStatus::workerNotRunning($jobId);
        } catch (Exception $e) {
            Log::warning('Failed to determine timeout type, using generic timeout', [
                'test_job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            return QueueWorkerStatus::timeout($jobId);
        }
    }

    /**
     * Clean up old test job data to prevent database/cache bloat.
     * 
     * @param int $olderThanHours Remove test jobs older than this many hours (default: 24)
     * @return int Number of test jobs cleaned up
     */
    public function cleanupOldTestJobs(int $olderThanHours = 24): int
    {
        try {
            // Use performance service for comprehensive cleanup
            $cleanupStats = $this->performanceService->performComprehensiveCleanup();

            Log::info('Automated cleanup completed via performance service', $cleanupStats);

            return $cleanupStats['test_jobs_cleaned'] ?? 0;
        } catch (Exception $e) {
            Log::error('Failed to perform automated cleanup', [
                'error' => $e->getMessage(),
                'older_than_hours' => $olderThanHours,
            ]);

            // Fallback to legacy cleanup method
            return $this->legacyCleanupOldTestJobs($olderThanHours);
        }
    }

    /**
     * Legacy cleanup method for fallback.
     * 
     * @param int $olderThanHours Remove test jobs older than this many hours
     * @return int Number of test jobs cleaned up
     */
    private function legacyCleanupOldTestJobs(int $olderThanHours): int
    {
        try {
            $cutoffTime = Carbon::now()->subHours($olderThanHours);
            $cleaned = 0;

            // Get job index from cache
            $jobIndex = Cache::get(self::INDEX_CACHE_KEY, []);
            $remainingJobs = [];

            foreach ($jobIndex as $jobData) {
                $jobId = $jobData['job_id'];
                $createdAt = Carbon::parse($jobData['created_at']);

                if ($createdAt->lt($cutoffTime)) {
                    // Remove old job from cache
                    $cacheKey = self::CACHE_PREFIX . $jobId;
                    if (Cache::forget($cacheKey)) {
                        $cleaned++;
                        Log::debug('Cleaned up old test job (legacy)', [
                            'test_job_id' => $jobId,
                            'created_at' => $createdAt->toISOString(),
                        ]);
                    }
                } else {
                    // Keep job in index
                    $remainingJobs[] = $jobData;
                }
            }

            // Update job index with remaining jobs
            if (count($remainingJobs) !== count($jobIndex)) {
                Cache::put(self::INDEX_CACHE_KEY, $remainingJobs, self::CACHE_TTL * 24); // 24 hour TTL for index
            }

            Log::info('Test job cleanup completed', [
                'cleaned_count' => $cleaned,
                'remaining_count' => count($remainingJobs),
                'cutoff_time' => $cutoffTime->toISOString(),
            ]);

            return $cleaned;
        } catch (Exception $e) {
            Log::error('Failed to cleanup old test jobs', [
                'error' => $e->getMessage(),
                'older_than_hours' => $olderThanHours,
            ]);

            return 0;
        }
    }

    /**
     * Generate a unique job ID for test job tracking.
     * 
     * @return string Unique job ID
     */
    private function generateUniqueJobId(): string
    {
        return 'test_' . Str::uuid()->toString();
    }

    /**
     * Initialize job status in cache with pending status.
     * 
     * @param string $jobId The job ID
     * @param int $delay The delay in seconds
     * @return void
     */
    private function initializeJobStatus(string $jobId, int $delay): void
    {
        $cacheKey = self::CACHE_PREFIX . $jobId;

        $initialStatus = [
            'test_job_id' => $jobId,
            'status' => 'pending',
            'message' => 'Test job dispatched and waiting for processing',
            'delay' => $delay,
            'dispatched_at' => Carbon::now()->toISOString(),
            'timeout_at' => Carbon::now()->addSeconds(self::DEFAULT_TIMEOUT)->toISOString(),
        ];

        Cache::put($cacheKey, $initialStatus, self::CACHE_TTL);
    }

    /**
     * Add job ID to index for cleanup tracking.
     * 
     * @param string $jobId The job ID to add
     * @return void
     */
    private function addJobToIndex(string $jobId): void
    {
        $jobIndex = Cache::get(self::INDEX_CACHE_KEY, []);

        $jobIndex[] = [
            'job_id' => $jobId,
            'created_at' => Carbon::now()->toISOString(),
        ];

        // Keep only last 100 jobs in index to prevent unlimited growth
        if (count($jobIndex) > 100) {
            $jobIndex = array_slice($jobIndex, -100);
        }

        Cache::put(self::INDEX_CACHE_KEY, $jobIndex, self::CACHE_TTL * 24);
    }

    /**
     * Check if a job has timed out and update status accordingly.
     * 
     * @param string $jobId The job ID
     * @param array $status Current job status
     * @return array Updated job status
     */
    private function checkForTimeout(string $jobId, array $status): array
    {
        $timeoutAt = Carbon::parse($status['timeout_at']);

        if (Carbon::now()->gt($timeoutAt)) {
            $status['status'] = 'timeout';
            $status['message'] = 'Test job timed out - queue worker may not be running';
            $status['timed_out_at'] = Carbon::now()->toISOString();

            // Update cache with timeout status
            $cacheKey = self::CACHE_PREFIX . $jobId;
            Cache::put($cacheKey, $status, self::CACHE_TTL);

            Log::warning('Test job timed out', [
                'test_job_id' => $jobId,
                'timeout_at' => $timeoutAt->toISOString(),
                'current_time' => Carbon::now()->toISOString(),
            ]);
        }

        return $status;
    }

    /**
     * Check if queue tables exist in the database.
     * 
     * @return bool True if queue tables exist
     */
    private function checkQueueTablesExist(): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable('jobs') &&
                DB::getSchemaBuilder()->hasTable('failed_jobs');
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get statistics about test jobs from cache.
     * 
     * @return array Test job statistics
     */
    private function getTestJobStatistics(): array
    {
        try {
            $jobIndex = Cache::get(self::INDEX_CACHE_KEY, []);
            $now = Carbon::now();
            $lastHour = $now->copy()->subHour();
            $last24Hours = $now->copy()->subHours(24);

            $stats = [
                'total_test_jobs' => count($jobIndex),
                'test_jobs_1h' => 0,
                'test_jobs_24h' => 0,
            ];

            foreach ($jobIndex as $jobData) {
                $createdAt = Carbon::parse($jobData['created_at']);

                if ($createdAt->gte($lastHour)) {
                    $stats['test_jobs_1h']++;
                }

                if ($createdAt->gte($last24Hours)) {
                    $stats['test_jobs_24h']++;
                }
            }

            return $stats;
        } catch (Exception $e) {
            return [
                'total_test_jobs' => 0,
                'test_jobs_1h' => 0,
                'test_jobs_24h' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get recent failed job details for admin context.
     * 
     * @param Carbon $since Get failed jobs since this time
     * @return array Recent failed job details
     */
    private function getRecentFailedJobDetails(Carbon $since): array
    {
        try {
            $failedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', $since)
                ->orderBy('failed_at', 'desc')
                ->limit(5) // Only get the 5 most recent
                ->get(['id', 'queue', 'payload', 'exception', 'failed_at']);

            $details = [];
            foreach ($failedJobs as $job) {
                $payload = json_decode($job->payload, true);
                $jobClass = $payload['displayName'] ?? 'Unknown Job';

                // Extract the main error message (first line of exception)
                $exceptionLines = explode("\n", $job->exception);
                $mainError = $exceptionLines[0] ?? 'Unknown error';

                // Clean up the error message
                if (strpos($mainError, ':') !== false) {
                    $parts = explode(':', $mainError, 2);
                    $mainError = trim($parts[1] ?? $parts[0]);
                }

                $details[] = [
                    'id' => $job->id,
                    'job_class' => $jobClass,
                    'queue' => $job->queue,
                    'error_message' => $mainError,
                    'failed_at' => $job->failed_at,
                ];
            }

            return $details;
        } catch (Exception $e) {
            Log::warning('Failed to get recent failed job details', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Assess overall queue health based on metrics.
     * 
     * @param array $metrics Current queue metrics
     * @return array Health assessment
     */
    private function assessQueueHealth(array $metrics): array
    {
        $jobStats = $metrics['job_statistics'];
        $stalledJobs = $metrics['stalled_jobs'];

        // Determine overall health status
        if ($jobStats['failed_jobs_1h'] > 5 || $stalledJobs > 3) {
            $status = 'critical';
            $message = 'Queue worker has critical issues - immediate attention required';
        } elseif ($jobStats['failed_jobs_1h'] > 2 || $stalledJobs > 1) {
            $status = 'warning';
            $message = 'Queue worker may have issues - monitoring recommended';
        } elseif ($jobStats['pending_jobs'] > 0 || $jobStats['failed_jobs_24h'] === 0) {
            $status = 'healthy';
            $message = 'Queue worker appears to be functioning normally';
        } else {
            $status = 'idle';
            $message = 'Queue worker is idle - no recent activity detected';
        }

        return [
            'overall_status' => $status,
            'health_message' => $message,
            'recommendations' => $this->getHealthRecommendations($status, $metrics),
        ];
    }

    /**
     * Get health recommendations based on queue status.
     * 
     * @param string $status Overall health status
     * @param array $metrics Queue metrics
     * @return array Recommendations
     */
    private function getHealthRecommendations(string $status, array $metrics): array
    {
        $recommendations = [];

        switch ($status) {
            case 'critical':
                $recommendations[] = 'Check if queue worker is running: php artisan queue:work';
                $recommendations[] = 'Review failed jobs: php artisan queue:failed';
                $recommendations[] = 'Consider restarting queue workers';
                break;

            case 'warning':
                $recommendations[] = 'Monitor queue worker performance';
                $recommendations[] = 'Check recent failed jobs for patterns';
                if ($metrics['stalled_jobs'] > 0) {
                    $recommendations[] = 'Restart queue workers to clear stalled jobs';
                }
                break;

            case 'idle':
                $recommendations[] = 'Queue worker appears idle - this may be normal';
                $recommendations[] = 'Dispatch a test job to verify worker functionality';
                break;

            case 'healthy':
            default:
                $recommendations[] = 'Queue worker is functioning normally';
                break;
        }

        return $recommendations;
    }

    /**
     * Execute a function with timeout handling.
     * 
     * @param callable $callback The function to execute
     * @param int $timeout Timeout in seconds
     * @return mixed The result of the callback or null on timeout
     * @throws Exception If the callback throws an exception
     */
    private function executeWithTimeout(callable $callback, int $timeout)
    {
        $startTime = microtime(true);

        try {
            // Set a reasonable timeout for the operation
            $originalTimeout = ini_get('default_socket_timeout');
            ini_set('default_socket_timeout', $timeout);

            $result = $callback();

            // Restore original timeout
            ini_set('default_socket_timeout', $originalTimeout);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::debug('Operation completed within timeout', [
                'duration_ms' => $duration,
                'timeout_seconds' => $timeout
            ]);

            return $result;
        } catch (Exception $e) {
            // Restore original timeout on error
            if (isset($originalTimeout)) {
                ini_set('default_socket_timeout', $originalTimeout);
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::warning('Operation failed within timeout period', [
                'duration_ms' => $duration,
                'timeout_seconds' => $timeout,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Initialize job status in cache with timeout handling.
     * 
     * @param string $jobId The job ID
     * @param int $delay The delay in seconds
     * @return void
     * @throws Exception If cache operation fails
     */
    private function initializeJobStatusWithTimeout(string $jobId, int $delay): void
    {
        $cacheKey = self::CACHE_PREFIX . $jobId;

        $initialStatus = [
            'test_job_id' => $jobId,
            'status' => 'pending',
            'message' => 'Test job dispatched and waiting for processing',
            'delay' => $delay,
            'dispatched_at' => Carbon::now()->toISOString(),
            'timeout_at' => Carbon::now()->addSeconds(self::DEFAULT_TIMEOUT)->toISOString(),
            'fallback' => false
        ];

        $this->executeWithTimeout(function () use ($cacheKey, $initialStatus) {
            if (!Cache::put($cacheKey, $initialStatus, self::CACHE_TTL)) {
                throw new Exception('Failed to store job status in cache');
            }
        }, 5);

        Log::debug('Job status initialized in cache', [
            'test_job_id' => $jobId,
            'cache_key' => $cacheKey,
            'timeout_at' => $initialStatus['timeout_at']
        ]);
    }

    /**
     * Add job ID to index with retry logic.
     * 
     * @param string $jobId The job ID to add
     * @return void
     * @throws Exception If all retry attempts fail
     */
    private function addJobToIndexWithRetry(string $jobId): void
    {
        $maxAttempts = 3;
        $lastException = null;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                if ($attempt > 0) {
                    usleep(500000); // 500ms delay between retries
                }

                $this->executeWithTimeout(function () use ($jobId) {
                    $jobIndex = Cache::get(self::INDEX_CACHE_KEY, []);

                    $jobIndex[] = [
                        'job_id' => $jobId,
                        'created_at' => Carbon::now()->toISOString(),
                    ];

                    // Keep only last 100 jobs in index to prevent unlimited growth
                    if (count($jobIndex) > 100) {
                        $jobIndex = array_slice($jobIndex, -100);
                    }

                    if (!Cache::put(self::INDEX_CACHE_KEY, $jobIndex, self::CACHE_TTL * 24)) {
                        throw new Exception('Failed to update job index in cache');
                    }
                }, 5);

                Log::debug('Job added to index successfully', [
                    'test_job_id' => $jobId,
                    'attempt' => $attempt + 1
                ]);

                return; // Success

            } catch (Exception $e) {
                $lastException = $e;

                Log::warning('Failed to add job to index, will retry', [
                    'test_job_id' => $jobId,
                    'attempt' => $attempt + 1,
                    'max_attempts' => $maxAttempts,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // All attempts failed - log but don't throw (index is not critical)
        Log::error('Failed to add job to index after all retries', [
            'test_job_id' => $jobId,
            'max_attempts' => $maxAttempts,
            'final_error' => $lastException ? $lastException->getMessage() : 'Unknown error'
        ]);
    }

    /**
     * Dispatch job with timeout handling.
     * 
     * @param string $jobId The job ID
     * @param int $delay The delay in seconds
     * @return void
     * @throws Exception If job dispatch fails
     */
    private function dispatchJobWithTimeout(string $jobId, int $delay): void
    {
        $this->executeWithTimeout(function () use ($jobId, $delay) {
            TestQueueJob::dispatch($jobId, $delay);
        }, 10); // 10 second timeout for job dispatch

        Log::debug('Test job dispatched to queue', [
            'test_job_id' => $jobId,
            'delay' => $delay
        ]);
    }

    /**
     * Check if job ID has valid format.
     * 
     * @param string $jobId The job ID to validate
     * @return bool True if valid format
     */
    private function isValidJobId(string $jobId): bool
    {
        return preg_match('/^test_[a-f0-9\-]{36}$/', $jobId) === 1;
    }

    /**
     * Check for timeout with fallback handling.
     * 
     * @param string $jobId The job ID
     * @param array $status Current job status
     * @return array Updated job status
     */
    private function checkForTimeoutWithFallback(string $jobId, array $status): array
    {
        try {
            $timeoutAt = Carbon::parse($status['timeout_at']);

            if (Carbon::now()->gt($timeoutAt)) {
                $status['status'] = 'timeout';
                $status['message'] = 'Test job timed out - queue worker may not be running';
                $status['timed_out_at'] = Carbon::now()->toISOString();
                $status['troubleshooting'] = [
                    'Check if queue worker is running: php artisan queue:work',
                    'Verify queue configuration in .env file',
                    'Check for failed jobs: php artisan queue:failed',
                    'Review application logs for errors'
                ];

                // Update cache with timeout status
                $cacheKey = self::CACHE_PREFIX . $jobId;
                try {
                    Cache::put($cacheKey, $status, self::CACHE_TTL);
                } catch (Exception $e) {
                    Log::warning('Failed to update cache with timeout status', [
                        'test_job_id' => $jobId,
                        'error' => $e->getMessage()
                    ]);
                }

                Log::warning('Test job timed out', [
                    'test_job_id' => $jobId,
                    'timeout_at' => $timeoutAt->toISOString(),
                    'current_time' => Carbon::now()->toISOString(),
                ]);
            }

            return $status;
        } catch (Exception $e) {
            Log::error('Error checking for job timeout', [
                'test_job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            // Return status as-is if we can't check timeout
            return $status;
        }
    }

    /**
     * Get error job status for failed operations.
     * 
     * @param string $jobId The job ID
     * @param Throwable $exception The exception that occurred
     * @return array Error job status
     */
    private function getErrorJobStatus(string $jobId, Throwable $exception): array
    {
        return [
            'test_job_id' => $jobId,
            'status' => 'error',
            'message' => 'Error checking job status - service temporarily unavailable',
            'error' => $exception->getMessage(),
            'error_type' => get_class($exception),
            'troubleshooting' => [
                'Check application logs for detailed error information',
                'Verify cache service is running and accessible',
                'Try refreshing the page and testing again',
                'Contact administrator if problem persists'
            ],
            'checked_at' => Carbon::now()->toISOString(),
            'fallback' => true
        ];
    }

    /**
     * Determine if a dispatch error should not be retried.
     * 
     * @param Exception $exception The exception to check
     * @return bool True if the exception should not be retried
     */
    private function shouldNotRetryDispatch(Exception $exception): bool
    {
        // Don't retry configuration errors or permanent failures
        $nonRetryableErrors = [
            'InvalidArgumentException',
            'BadMethodCallException',
            'LogicException'
        ];

        $exceptionClass = get_class($exception);

        foreach ($nonRetryableErrors as $errorClass) {
            if (strpos($exceptionClass, $errorClass) !== false) {
                return true;
            }
        }

        // Don't retry if error message indicates a permanent issue
        $message = strtolower($exception->getMessage());
        $permanentErrorIndicators = [
            'class not found',
            'method does not exist',
            'invalid configuration',
            'permission denied'
        ];

        foreach ($permanentErrorIndicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create appropriate error status from exception type.
     * 
     * @param Exception $exception The exception that occurred
     * @return QueueWorkerStatus Appropriate error status
     */
    private function createErrorStatusFromException(Exception $exception): QueueWorkerStatus
    {
        $errorMessage = $exception->getMessage();
        $exceptionClass = get_class($exception);

        // Determine error type based on exception class and message
        if ($this->isConfigurationError($exception)) {
            return QueueWorkerStatus::configurationError($errorMessage);
        }

        if ($this->isDatabaseError($exception)) {
            return QueueWorkerStatus::databaseError($errorMessage);
        }

        if ($this->isPermissionError($exception)) {
            return QueueWorkerStatus::permissionError($errorMessage);
        }

        if ($this->isDispatchError($exception)) {
            return QueueWorkerStatus::dispatchFailed($errorMessage);
        }

        if ($this->isNetworkError($exception)) {
            return QueueWorkerStatus::networkError($errorMessage);
        }

        // Default to generic failed status
        return QueueWorkerStatus::failed($errorMessage);
    }

    /**
     * Check if exception indicates a dispatch error.
     * 
     * @param Exception $exception The exception to check
     * @return bool True if it's a dispatch error
     */
    private function isDispatchError(Exception $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $dispatchErrorIndicators = [
            'failed to dispatch',
            'queue connection',
            'database connection',
            'table doesn\'t exist',
            'jobs table',
            'failed_jobs table'
        ];

        foreach ($dispatchErrorIndicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if exception indicates a network error.
     * 
     * @param Exception $exception The exception to check
     * @return bool True if it's a network error
     */
    private function isNetworkError(Exception $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $networkErrorIndicators = [
            'connection refused',
            'timeout',
            'network unreachable',
            'host unreachable',
            'connection timed out',
            'could not resolve host'
        ];

        foreach ($networkErrorIndicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build enhanced error message with troubleshooting context.
     * 
     * @param Exception|null $exception The final exception
     * @param int $maxAttempts Number of attempts made
     * @return string Enhanced error message
     */
    private function buildEnhancedErrorMessage(?Exception $exception, int $maxAttempts): string
    {
        if (!$exception) {
            return "Failed to dispatch test job after {$maxAttempts} attempts: Unknown error";
        }

        $baseMessage = "Failed to dispatch test job after {$maxAttempts} attempts";
        $errorDetails = $exception->getMessage();

        // Add context based on error type
        if ($this->isConfigurationError($exception)) {
            return "{$baseMessage}: Configuration issue - {$errorDetails}";
        }

        if ($this->isDatabaseError($exception)) {
            return "{$baseMessage}: Database connectivity issue - {$errorDetails}";
        }

        if ($this->isPermissionError($exception)) {
            return "{$baseMessage}: File permission issue - {$errorDetails}";
        }

        if ($this->isDispatchError($exception)) {
            return "{$baseMessage}: Queue dispatch issue - {$errorDetails}";
        }

        if ($this->isNetworkError($exception)) {
            return "{$baseMessage}: Network connectivity issue - {$errorDetails}";
        }

        return "{$baseMessage}: {$errorDetails}";
    }

    /**
     * Check if exception indicates a configuration error.
     * 
     * @param Exception $exception The exception to check
     * @return bool True if it's a configuration error
     */
    private function isConfigurationError(Exception $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $configErrorIndicators = [
            'invalid configuration',
            'configuration error',
            'queue connection',
            'driver not supported',
            'invalid queue driver',
            'queue_connection',
            'config not found',
            'missing configuration'
        ];

        foreach ($configErrorIndicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                return true;
            }
        }

        // Check exception types
        $exceptionClass = get_class($exception);
        return strpos($exceptionClass, 'InvalidArgumentException') !== false ||
            strpos($exceptionClass, 'ConfigurationException') !== false;
    }

    /**
     * Check if exception indicates a database error.
     * 
     * @param Exception $exception The exception to check
     * @return bool True if it's a database error
     */
    private function isDatabaseError(Exception $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $dbErrorIndicators = [
            'database connection',
            'connection refused',
            'access denied',
            'unknown database',
            'table doesn\'t exist',
            'jobs table',
            'failed_jobs table',
            'sqlstate',
            'pdo exception',
            'mysql',
            'postgresql',
            'sqlite'
        ];

        foreach ($dbErrorIndicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                return true;
            }
        }

        // Check exception types
        $exceptionClass = get_class($exception);
        return $exception instanceof PDOException ||
            $exception instanceof QueryException ||
            strpos($exceptionClass, 'DatabaseException') !== false;
    }

    /**
     * Check if exception indicates a permission error.
     * 
     * @param Exception $exception The exception to check
     * @return bool True if it's a permission error
     */
    private function isPermissionError(Exception $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $permissionErrorIndicators = [
            'permission denied',
            'access denied',
            'forbidden',
            'not writable',
            'cannot write',
            'file not found',
            'directory not found',
            'failed to open stream',
            'no such file or directory'
        ];

        foreach ($permissionErrorIndicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Initialize job status in cache with configurable timeout.
     * 
     * @param string $jobId The job ID
     * @param int $delay The delay in seconds
     * @param int $timeout The timeout in seconds
     * @return void
     * @throws Exception If cache operation fails
     */
    private function initializeJobStatusWithConfigurableTimeout(string $jobId, int $delay, int $timeout): void
    {
        $cacheKey = self::CACHE_PREFIX . $jobId;

        $initialStatus = [
            'test_job_id' => $jobId,
            'status' => 'pending',
            'message' => 'Test job dispatched and waiting for processing',
            'delay' => $delay,
            'timeout_seconds' => $timeout,
            'dispatched_at' => Carbon::now()->toISOString(),
            'timeout_at' => Carbon::now()->addSeconds($timeout)->toISOString(),
            'fallback' => false
        ];

        $this->executeWithTimeout(function () use ($cacheKey, $initialStatus) {
            if (!Cache::put($cacheKey, $initialStatus, self::CACHE_TTL)) {
                throw new Exception('Failed to store job status in cache');
            }
        }, 5);

        Log::debug('Job status initialized in cache with configurable timeout', [
            'test_job_id' => $jobId,
            'cache_key' => $cacheKey,
            'timeout_seconds' => $timeout,
            'timeout_at' => $initialStatus['timeout_at']
        ]);
    }
}
