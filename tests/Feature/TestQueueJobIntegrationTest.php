<?php

namespace Tests\Feature;

use App\Jobs\TestQueueJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Illuminate\Support\Str;

class TestQueueJobIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache before each test
        Cache::flush();
    }

    public function test_job_can_be_dispatched_and_processed(): void
    {
        // Use sync queue driver for immediate processing
        config(['queue.default' => 'sync']);
        
        $testJobId = 'integration-test-' . Str::random(8);
        
        // Dispatch the job
        TestQueueJob::dispatch($testJobId, 0);
        
        // Verify job completed successfully
        $status = TestQueueJob::getJobStatus($testJobId);
        
        $this->assertNotNull($status);
        $this->assertEquals('completed', $status['status']);
        $this->assertEquals($testJobId, $status['test_job_id']);
        $this->assertTrue($status['success']);
        $this->assertArrayHasKey('processing_time_ms', $status);
        $this->assertArrayHasKey('completed_at', $status);
    }

    public function test_job_can_be_dispatched_with_delay(): void
    {
        // Use sync queue driver for immediate processing
        config(['queue.default' => 'sync']);
        
        $testJobId = 'integration-delay-test-' . Str::random(8);
        $delay = 1; // 1 second delay
        
        $startTime = microtime(true);
        
        // Dispatch the job with delay
        TestQueueJob::dispatch($testJobId, $delay);
        
        $endTime = microtime(true);
        $actualTime = $endTime - $startTime;
        
        // Verify job took at least the delay time
        $this->assertGreaterThanOrEqual($delay, $actualTime);
        
        // Verify job completed successfully
        $status = TestQueueJob::getJobStatus($testJobId);
        
        $this->assertNotNull($status);
        $this->assertEquals('completed', $status['status']);
        $this->assertTrue($status['success']);
    }

    public function test_job_status_can_be_retrieved_after_dispatch(): void
    {
        // Use database queue driver to test async behavior
        config(['queue.default' => 'database']);
        
        $testJobId = 'async-test-' . Str::random(8);
        
        // Dispatch the job (it won't process immediately with database driver)
        TestQueueJob::dispatch($testJobId, 0);
        
        // Initially, there should be no status (job hasn't processed yet)
        $initialStatus = TestQueueJob::getJobStatus($testJobId);
        $this->assertNull($initialStatus);
        
        // Process the queue manually
        $this->artisan('queue:work', [
            '--once' => true,
            '--queue' => 'default'
        ]);
        
        // Now the job should have completed
        $finalStatus = TestQueueJob::getJobStatus($testJobId);
        
        $this->assertNotNull($finalStatus);
        $this->assertEquals('completed', $finalStatus['status']);
        $this->assertEquals($testJobId, $finalStatus['test_job_id']);
    }

    public function test_multiple_jobs_can_be_tracked_independently(): void
    {
        // Use sync queue driver for immediate processing
        config(['queue.default' => 'sync']);
        
        $testJobId1 = 'multi-test-1-' . Str::random(8);
        $testJobId2 = 'multi-test-2-' . Str::random(8);
        
        // Dispatch multiple jobs
        TestQueueJob::dispatch($testJobId1, 0);
        TestQueueJob::dispatch($testJobId2, 0);
        
        // Verify both jobs completed independently
        $status1 = TestQueueJob::getJobStatus($testJobId1);
        $status2 = TestQueueJob::getJobStatus($testJobId2);
        
        $this->assertNotNull($status1);
        $this->assertNotNull($status2);
        
        $this->assertEquals('completed', $status1['status']);
        $this->assertEquals('completed', $status2['status']);
        
        $this->assertEquals($testJobId1, $status1['test_job_id']);
        $this->assertEquals($testJobId2, $status2['test_job_id']);
        
        // Verify they have different processing times (likely)
        $this->assertNotEquals($status1['processing_time_ms'], $status2['processing_time_ms']);
    }

    public function test_job_cache_key_generation_is_consistent(): void
    {
        $testJobId = 'cache-key-test';
        
        $key1 = TestQueueJob::getCacheKey($testJobId);
        $key2 = TestQueueJob::getCacheKey($testJobId);
        
        $this->assertEquals($key1, $key2);
        $this->assertEquals('test_queue_job_cache-key-test', $key1);
    }

    protected function tearDown(): void
    {
        // Clean up any test jobs from cache
        Cache::flush();
        parent::tearDown();
    }
}