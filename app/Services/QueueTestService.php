<?php

namespace App\Services;

use App\Jobs\TestQueueJob;
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
     * Clean up old test job data to prevent database/cache bloat.
     * 
     * @param int $olderThanHours Remove test jobs older than this many hours (default: 24)
     * @return int Number of test jobs cleaned up
     */
    public function cleanupOldTestJobs(int $olderThanHours = 24): int
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
                        Log::debug('Cleaned up old test job', [
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
}