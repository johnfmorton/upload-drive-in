<?php

namespace Tests\Unit\Services;

use App\Services\QueueTestService;
use App\Services\QueueWorkerStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Carbon\Carbon;
use Exception;

class QueueTestServiceCachingTest extends TestCase
{
    private QueueTestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QueueTestService();
        
        // Clear any existing cache
        Cache::forget(QueueWorkerStatus::CACHE_KEY);
    }

    protected function tearDown(): void
    {
        // Clean up cache after each test
        Cache::forget(QueueWorkerStatus::CACHE_KEY);
        parent::tearDown();
    }

    public function test_get_cached_queue_worker_status_returns_not_tested_when_no_cache()
    {
        $status = $this->service->getCachedQueueWorkerStatus();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_NOT_TESTED, $status->status);
        $this->assertEquals('Click the Test Queue Worker button below', $status->message);
        $this->assertTrue($status->canRetry);
    }

    public function test_get_cached_queue_worker_status_returns_cached_data()
    {
        // Cache a completed status
        $cachedData = [
            'status' => QueueWorkerStatus::STATUS_COMPLETED,
            'message' => 'Queue worker is functioning properly (1.23s)',
            'test_completed_at' => Carbon::now()->toISOString(),
            'processing_time' => 1.23,
            'test_job_id' => 'test_12345',
            'can_retry' => true
        ];
        
        Cache::put(QueueWorkerStatus::CACHE_KEY, $cachedData, QueueWorkerStatus::CACHE_TTL);
        
        $status = $this->service->getCachedQueueWorkerStatus();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $status->status);
        $this->assertEquals('Queue worker is functioning properly (1.23s)', $status->message);
        $this->assertEquals(1.23, $status->processingTime);
        $this->assertEquals('test_12345', $status->testJobId);
        $this->assertTrue($status->canRetry);
    }

    public function test_get_cached_queue_worker_status_handles_expired_cache()
    {
        // Cache an expired completed status
        $expiredTime = Carbon::now()->subHours(2);
        $cachedData = [
            'status' => QueueWorkerStatus::STATUS_COMPLETED,
            'message' => 'Queue worker is functioning properly (1.23s)',
            'test_completed_at' => $expiredTime->toISOString(),
            'processing_time' => 1.23,
            'test_job_id' => 'test_12345',
            'can_retry' => true
        ];
        
        Cache::put(QueueWorkerStatus::CACHE_KEY, $cachedData, QueueWorkerStatus::CACHE_TTL);
        
        $status = $this->service->getCachedQueueWorkerStatus();
        
        // Should return not tested status and clear the expired cache
        $this->assertEquals(QueueWorkerStatus::STATUS_NOT_TESTED, $status->status);
        $this->assertEquals('Click the Test Queue Worker button below', $status->message);
        
        // Verify cache was cleared
        $this->assertNull(Cache::get(QueueWorkerStatus::CACHE_KEY));
    }

    public function test_get_cached_queue_worker_status_preserves_non_completed_statuses()
    {
        // Cache a testing status (should not expire)
        $cachedData = [
            'status' => QueueWorkerStatus::STATUS_TESTING,
            'message' => 'Testing queue worker...',
            'test_job_id' => 'test_12345',
            'can_retry' => false
        ];
        
        Cache::put(QueueWorkerStatus::CACHE_KEY, $cachedData, QueueWorkerStatus::CACHE_TTL);
        
        $status = $this->service->getCachedQueueWorkerStatus();
        
        // Should return the cached testing status (not expired)
        $this->assertEquals(QueueWorkerStatus::STATUS_TESTING, $status->status);
        $this->assertEquals('Testing queue worker...', $status->message);
        $this->assertEquals('test_12345', $status->testJobId);
        
        // Verify cache was not cleared
        $this->assertNotNull(Cache::get(QueueWorkerStatus::CACHE_KEY));
    }

    public function test_cache_queue_worker_status_stores_data_successfully()
    {
        $status = QueueWorkerStatus::completed(1.23, 'test_12345');
        
        $result = $this->service->cacheQueueWorkerStatus($status);
        
        $this->assertTrue($result);
        
        // Verify data was cached
        $cachedData = Cache::get(QueueWorkerStatus::CACHE_KEY);
        $this->assertIsArray($cachedData);
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $cachedData['status']);
        $this->assertEquals(1.23, $cachedData['processing_time']);
        $this->assertEquals('test_12345', $cachedData['test_job_id']);
    }

    public function test_invalidate_queue_worker_status_clears_cache()
    {
        // First cache some data
        $status = QueueWorkerStatus::completed(1.23, 'test_12345');
        $this->service->cacheQueueWorkerStatus($status);
        
        // Verify data is cached
        $this->assertNotNull(Cache::get(QueueWorkerStatus::CACHE_KEY));
        
        // Invalidate cache
        $result = $this->service->invalidateQueueWorkerStatus();
        
        $this->assertTrue($result);
        $this->assertNull(Cache::get(QueueWorkerStatus::CACHE_KEY));
    }

    public function test_update_queue_worker_status_from_job_success()
    {
        $jobId = 'test_12345';
        $processingTime = 2.45;
        
        $status = $this->service->updateQueueWorkerStatusFromJob($jobId, true, $processingTime);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $status->status);
        $this->assertEquals($processingTime, $status->processingTime);
        $this->assertEquals($jobId, $status->testJobId);
        $this->assertStringContainsString('2.45s', $status->message);
        
        // Verify status was cached
        $cachedStatus = $this->service->getCachedQueueWorkerStatus();
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $cachedStatus->status);
        $this->assertEquals($processingTime, $cachedStatus->processingTime);
    }

    public function test_update_queue_worker_status_from_job_failure()
    {
        $jobId = 'test_12345';
        $errorMessage = 'Job execution failed';
        
        $status = $this->service->updateQueueWorkerStatusFromJob($jobId, false, null, $errorMessage);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals($errorMessage, $status->errorMessage);
        $this->assertEquals($jobId, $status->testJobId);
        $this->assertEquals('Queue worker test failed', $status->message);
        
        // Verify status was cached
        $cachedStatus = $this->service->getCachedQueueWorkerStatus();
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $cachedStatus->status);
        $this->assertEquals($errorMessage, $cachedStatus->errorMessage);
    }

    public function test_update_queue_worker_status_from_job_failure_without_error_message()
    {
        $jobId = 'test_12345';
        
        $status = $this->service->updateQueueWorkerStatusFromJob($jobId, false);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Test job failed without specific error', $status->errorMessage);
        $this->assertEquals($jobId, $status->testJobId);
    }

    public function test_cache_expiration_handling()
    {
        // Test with a status that should not be expired
        $recentTime = Carbon::now()->subMinutes(30);
        $cachedData = [
            'status' => QueueWorkerStatus::STATUS_COMPLETED,
            'message' => 'Queue worker is functioning properly (1.23s)',
            'test_completed_at' => $recentTime->toISOString(),
            'processing_time' => 1.23,
            'test_job_id' => 'test_12345',
            'can_retry' => true
        ];
        
        Cache::put(QueueWorkerStatus::CACHE_KEY, $cachedData, QueueWorkerStatus::CACHE_TTL);
        
        $status = $this->service->getCachedQueueWorkerStatus();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $status->status);
        $this->assertFalse($status->isExpired());
    }

    public function test_cache_handles_malformed_data()
    {
        // Cache malformed data
        Cache::put(QueueWorkerStatus::CACHE_KEY, 'invalid_data', QueueWorkerStatus::CACHE_TTL);
        
        $status = $this->service->getCachedQueueWorkerStatus();
        
        // Should handle gracefully and return not tested status
        $this->assertEquals(QueueWorkerStatus::STATUS_NOT_TESTED, $status->status);
    }

    public function test_cache_handles_partial_data()
    {
        // Cache partial data (missing some fields)
        $partialData = [
            'status' => QueueWorkerStatus::STATUS_TESTING,
            'message' => 'Testing...'
            // Missing other fields
        ];
        
        Cache::put(QueueWorkerStatus::CACHE_KEY, $partialData, QueueWorkerStatus::CACHE_TTL);
        
        $status = $this->service->getCachedQueueWorkerStatus();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_TESTING, $status->status);
        $this->assertEquals('Testing...', $status->message);
        $this->assertNull($status->testJobId);
        $this->assertTrue($status->canRetry); // Should use default value
    }

    public function test_cache_ttl_constant()
    {
        $this->assertEquals(3600, QueueWorkerStatus::CACHE_TTL);
    }

    public function test_cache_key_constant()
    {
        $this->assertEquals('setup_queue_worker_status', QueueWorkerStatus::CACHE_KEY);
    }

    public function test_caching_preserves_all_status_fields()
    {
        $originalStatus = QueueWorkerStatus::failed(
            'Test error message',
            'test_12345',
            ['Step 1', 'Step 2', 'Step 3']
        );
        
        // Cache and retrieve
        $this->service->cacheQueueWorkerStatus($originalStatus);
        $retrievedStatus = $this->service->getCachedQueueWorkerStatus();
        
        $this->assertEquals($originalStatus->status, $retrievedStatus->status);
        $this->assertEquals($originalStatus->message, $retrievedStatus->message);
        $this->assertEquals($originalStatus->errorMessage, $retrievedStatus->errorMessage);
        $this->assertEquals($originalStatus->testJobId, $retrievedStatus->testJobId);
        $this->assertEquals($originalStatus->troubleshooting, $retrievedStatus->troubleshooting);
        $this->assertEquals($originalStatus->canRetry, $retrievedStatus->canRetry);
    }
}