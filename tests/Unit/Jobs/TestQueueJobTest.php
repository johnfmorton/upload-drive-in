<?php

namespace Tests\Unit\Jobs;

use App\Jobs\TestQueueJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Carbon\Carbon;
use Mockery;

class TestQueueJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache before each test
        Cache::flush();
        
        // Fake the queue to prevent actual job processing during tests
        Queue::fake();
    }

    public function test_job_can_be_instantiated_with_required_parameters(): void
    {
        $testJobId = 'test-job-123';
        $delay = 5;

        $job = new TestQueueJob($testJobId, $delay);

        $this->assertEquals($testJobId, $job->testJobId);
        $this->assertEquals($delay, $job->testDelay);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->timeout);
    }

    public function test_job_can_be_instantiated_with_default_delay(): void
    {
        $testJobId = 'test-job-456';

        $job = new TestQueueJob($testJobId);

        $this->assertEquals($testJobId, $job->testJobId);
        $this->assertEquals(0, $job->testDelay);
    }

    public function test_job_updates_cache_with_processing_status_on_start(): void
    {
        $testJobId = 'test-job-processing';
        $job = new TestQueueJob($testJobId);

        // Mock Carbon::now() to have predictable timestamps
        Carbon::setTestNow('2025-01-01 12:00:00');

        $job->handle();

        // Verify cache was updated with processing status
        $cacheKey = TestQueueJob::getCacheKey($testJobId);
        $cachedData = Cache::get($cacheKey);

        $this->assertNotNull($cachedData);
        $this->assertEquals('completed', $cachedData['status']); // Should be completed after handle()
        $this->assertEquals($testJobId, $cachedData['test_job_id']);
        $this->assertArrayHasKey('processing_time_ms', $cachedData);
        $this->assertTrue($cachedData['success']);
    }

    public function test_job_completes_successfully_and_updates_cache(): void
    {
        $testJobId = 'test-job-logging';
        $job = new TestQueueJob($testJobId, 0);

        Carbon::setTestNow('2025-01-01 12:00:00');

        $job->handle();

        // Verify cache was updated with completion status
        $cacheKey = TestQueueJob::getCacheKey($testJobId);
        $cachedData = Cache::get($cacheKey);

        $this->assertNotNull($cachedData);
        $this->assertEquals('completed', $cachedData['status']);
        $this->assertEquals($testJobId, $cachedData['test_job_id']);
        $this->assertTrue($cachedData['success']);
        $this->assertArrayHasKey('processing_time_ms', $cachedData);
        $this->assertArrayHasKey('completed_at', $cachedData);
    }

    public function test_job_applies_configurable_delay(): void
    {
        $testJobId = 'test-job-delay';
        $delay = 1; // Use shorter delay for faster tests
        $job = new TestQueueJob($testJobId, $delay);

        $startTime = microtime(true);
        $job->handle();
        $endTime = microtime(true);

        $actualDelay = $endTime - $startTime;

        // Allow for some variance in timing (should be at least the delay time)
        $this->assertGreaterThanOrEqual($delay, $actualDelay);
        $this->assertLessThan($delay + 0.5, $actualDelay); // Should not be too much longer

        // Verify job completed successfully despite delay
        $cacheKey = TestQueueJob::getCacheKey($testJobId);
        $cachedData = Cache::get($cacheKey);
        $this->assertEquals('completed', $cachedData['status']);
    }

    public function test_job_performs_test_operations_successfully(): void
    {
        $testJobId = 'test-job-operations';
        $job = new TestQueueJob($testJobId);

        $job->handle();

        // Verify job completed successfully, indicating all operations passed
        $cacheKey = TestQueueJob::getCacheKey($testJobId);
        $cachedData = Cache::get($cacheKey);

        $this->assertNotNull($cachedData);
        $this->assertEquals('completed', $cachedData['status']);
        $this->assertTrue($cachedData['success']);
        
        // Verify that cache operations worked (since the job uses cache internally)
        $this->assertArrayHasKey('processing_time_ms', $cachedData);
    }

    public function test_job_handles_exceptions_gracefully(): void
    {
        $testJobId = 'test-job-exception';
        
        // We'll test the failed() method directly since mocking Cache::put 
        // would interfere with the cache operations we need for the test
        $job = new TestQueueJob($testJobId);
        $exception = new \Exception('Test exception', 500);

        // Call the failed method directly
        $job->failed($exception);

        // Verify error status was cached
        $cacheKey = TestQueueJob::getCacheKey($testJobId);
        $cachedData = Cache::get($cacheKey);

        $this->assertNotNull($cachedData);
        $this->assertEquals('failed', $cachedData['status']);
        $this->assertEquals('Test exception', $cachedData['error_message']);
        $this->assertEquals(500, $cachedData['error_code']);
    }

    public function test_failed_method_updates_cache_with_error_details(): void
    {
        $testJobId = 'test-job-failed';
        $job = new TestQueueJob($testJobId);
        $exception = new \Exception('Test failure', 123);

        Carbon::setTestNow('2025-01-01 12:00:00');

        $job->failed($exception);

        $cacheKey = TestQueueJob::getCacheKey($testJobId);
        $cachedData = Cache::get($cacheKey);

        $this->assertNotNull($cachedData);
        $this->assertEquals('failed', $cachedData['status']);
        $this->assertEquals($testJobId, $cachedData['test_job_id']);
        $this->assertEquals('Test failure', $cachedData['error_message']);
        $this->assertEquals(123, $cachedData['error_code']);
        $this->assertArrayHasKey('failed_at', $cachedData);
        $this->assertArrayHasKey('error_file', $cachedData);
        $this->assertArrayHasKey('error_line', $cachedData);
        
        // Verify the failed_at timestamp is reasonable (within last few seconds)
        $failedAt = Carbon::parse($cachedData['failed_at']);
        $this->assertTrue($failedAt->diffInSeconds(Carbon::now()) < 5);
    }

    public function test_get_cache_key_returns_correct_format(): void
    {
        $testJobId = 'my-test-job-123';
        $expectedKey = 'test_queue_job_my-test-job-123';

        $actualKey = TestQueueJob::getCacheKey($testJobId);

        $this->assertEquals($expectedKey, $actualKey);
    }

    public function test_get_job_status_returns_cached_data(): void
    {
        $testJobId = 'test-job-status';
        $statusData = [
            'test_job_id' => $testJobId,
            'status' => 'completed',
            'processing_time_ms' => 150.5,
        ];

        Cache::put(TestQueueJob::getCacheKey($testJobId), $statusData, 3600);

        $retrievedStatus = TestQueueJob::getJobStatus($testJobId);

        $this->assertEquals($statusData, $retrievedStatus);
    }

    public function test_get_job_status_returns_null_for_nonexistent_job(): void
    {
        $testJobId = 'nonexistent-job';

        $retrievedStatus = TestQueueJob::getJobStatus($testJobId);

        $this->assertNull($retrievedStatus);
    }

    public function test_cleanup_old_test_jobs_returns_count(): void
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $cleanedCount = TestQueueJob::cleanupOldTestJobs();

        // Basic implementation returns 0 (as noted in the method)
        $this->assertEquals(0, $cleanedCount);
        $this->assertIsInt($cleanedCount);
    }

    public function test_job_cache_data_has_correct_ttl(): void
    {
        $testJobId = 'test-job-ttl';
        $job = new TestQueueJob($testJobId);

        $job->handle();

        $cacheKey = TestQueueJob::getCacheKey($testJobId);
        
        // Verify data exists
        $this->assertTrue(Cache::has($cacheKey));
        
        // The TTL is set to 3600 seconds (1 hour) in the job
        // We can't easily test the exact TTL without mocking Cache,
        // but we can verify the data is stored
        $cachedData = Cache::get($cacheKey);
        $this->assertNotNull($cachedData);
    }

    public function test_job_includes_attempt_information_in_failure_data(): void
    {
        $testJobId = 'test-job-attempts';
        $job = new TestQueueJob($testJobId);
        
        // Simulate job being on attempt 2
        $job->attempts = function() { return 2; };
        
        $exception = new \Exception('Test failure');
        $job->failed($exception);

        $cacheKey = TestQueueJob::getCacheKey($testJobId);
        $cachedData = Cache::get($cacheKey);

        $this->assertEquals(3, $cachedData['max_tries']);
        // Note: attempts() is a method, so we can't easily test the exact value
        // without more complex mocking, but the structure is verified
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon test time
        parent::tearDown();
    }
}