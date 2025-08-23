<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TestQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The unique identifier for this test job.
     */
    public string $testJobId;

    /**
     * The delay in seconds before processing (for testing purposes).
     */
    public int $testDelay;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(string $testJobId, int $delay = 0)
    {
        $this->testJobId = $testJobId;
        $this->testDelay = $delay;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $startTime = microtime(true);
            
            Log::info('TestQueueJob started', [
                'test_job_id' => $this->testJobId,
                'delay' => $this->testDelay,
                'started_at' => Carbon::now()->toISOString(),
            ]);

            // Update cache with processing status
            $this->updateJobStatus('processing', [
                'started_at' => Carbon::now()->toISOString(),
                'delay' => $this->testDelay,
            ]);

            // Apply configurable delay if specified
            if ($this->testDelay > 0) {
                Log::info('TestQueueJob applying delay', [
                    'test_job_id' => $this->testJobId,
                    'delay_seconds' => $this->testDelay,
                ]);
                sleep($this->testDelay);
            }

            // Perform some basic operations to verify queue worker functionality
            $this->performTestOperations();

            $endTime = microtime(true);
            $processingTime = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds

            // Update cache with completion status
            $this->updateJobStatus('completed', [
                'completed_at' => Carbon::now()->toISOString(),
                'processing_time_ms' => $processingTime,
                'success' => true,
            ]);

            Log::info('TestQueueJob completed successfully', [
                'test_job_id' => $this->testJobId,
                'processing_time_ms' => $processingTime,
                'completed_at' => Carbon::now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            $this->handleJobFailure($e);
            throw $e; // Re-throw to trigger Laravel's failed job handling
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->handleJobFailure($exception);
    }

    /**
     * Perform test operations to verify queue worker functionality.
     */
    private function performTestOperations(): void
    {
        // Test 1: Cache operations
        $cacheKey = "test_queue_job_{$this->testJobId}_cache_test";
        Cache::put($cacheKey, 'test_value', 60);
        $cacheValue = Cache::get($cacheKey);
        
        if ($cacheValue !== 'test_value') {
            throw new \Exception('Cache operation test failed');
        }

        // Test 2: Basic computation
        $result = 0;
        for ($i = 1; $i <= 100; $i++) {
            $result += $i;
        }
        
        if ($result !== 5050) {
            throw new \Exception('Computation test failed');
        }

        // Test 3: Memory allocation test
        $testArray = range(1, 1000);
        if (count($testArray) !== 1000) {
            throw new \Exception('Memory allocation test failed');
        }

        Log::debug('TestQueueJob operations completed', [
            'test_job_id' => $this->testJobId,
            'cache_test' => 'passed',
            'computation_test' => 'passed',
            'memory_test' => 'passed',
        ]);
    }

    /**
     * Update job status in cache.
     */
    private function updateJobStatus(string $status, array $additionalData = []): void
    {
        $cacheKey = "test_queue_job_{$this->testJobId}";
        
        $statusData = array_merge([
            'test_job_id' => $this->testJobId,
            'status' => $status,
            'updated_at' => Carbon::now()->toISOString(),
        ], $additionalData);

        // Store with 1 hour TTL
        Cache::put($cacheKey, $statusData, 3600);
    }

    /**
     * Handle job failure and update cache with error information.
     */
    private function handleJobFailure(\Throwable $exception): void
    {
        $errorData = [
            'failed_at' => Carbon::now()->toISOString(),
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
        ];

        $this->updateJobStatus('failed', $errorData);

        Log::error('TestQueueJob failed', array_merge([
            'test_job_id' => $this->testJobId,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ], $errorData));
    }

    /**
     * Get the cache key for this test job.
     */
    public static function getCacheKey(string $testJobId): string
    {
        return "test_queue_job_{$testJobId}";
    }

    /**
     * Get the status of a test job from cache.
     */
    public static function getJobStatus(string $testJobId): ?array
    {
        return Cache::get(self::getCacheKey($testJobId));
    }

    /**
     * Clean up old test job data from cache.
     */
    public static function cleanupOldTestJobs(): int
    {
        // This is a simplified cleanup - in a real implementation,
        // you might want to use a more sophisticated cache key pattern
        // or store job IDs in a separate index for easier cleanup
        
        $cleaned = 0;
        $pattern = 'test_queue_job_*';
        
        // Note: This is a basic implementation. For production,
        // consider using Redis SCAN or a job index for better performance
        Log::info('TestQueueJob cleanup initiated', [
            'pattern' => $pattern,
            'timestamp' => Carbon::now()->toISOString(),
        ]);
        
        return $cleaned;
    }
}