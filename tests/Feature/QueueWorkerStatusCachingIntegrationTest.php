<?php

namespace Tests\Feature;

use App\Services\QueueTestService;
use App\Services\QueueWorkerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Carbon\Carbon;

class QueueWorkerStatusCachingIntegrationTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_complete_queue_worker_status_caching_workflow()
    {
        // 1. Initial state - no cache
        $status = $this->service->getCachedQueueWorkerStatus();
        $this->assertEquals(QueueWorkerStatus::STATUS_NOT_TESTED, $status->status);
        $this->assertEquals('Click the Test Queue Worker button below', $status->message);

        // 2. Update to testing status
        $testingStatus = QueueWorkerStatus::testing('test_12345', 'Testing queue worker...');
        $this->service->cacheQueueWorkerStatus($testingStatus);

        $cachedStatus = $this->service->getCachedQueueWorkerStatus();
        $this->assertEquals(QueueWorkerStatus::STATUS_TESTING, $cachedStatus->status);
        $this->assertEquals('test_12345', $cachedStatus->testJobId);
        $this->assertFalse($cachedStatus->canRetry);

        // 3. Update to completed status
        $completedStatus = $this->service->updateQueueWorkerStatusFromJob('test_12345', true, 1.23);
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $completedStatus->status);
        $this->assertEquals(1.23, $completedStatus->processingTime);

        // 4. Verify completed status persists
        $persistedStatus = $this->service->getCachedQueueWorkerStatus();
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $persistedStatus->status);
        $this->assertEquals(1.23, $persistedStatus->processingTime);
        $this->assertFalse($persistedStatus->isExpired());

        // 5. Simulate cache expiration by manually setting old completion time
        $expiredData = $persistedStatus->toArray();
        $expiredData['test_completed_at'] = Carbon::now()->subHours(2)->toISOString();
        Cache::put(QueueWorkerStatus::CACHE_KEY, $expiredData, QueueWorkerStatus::CACHE_TTL);

        // 6. Verify expired cache is cleared and returns not tested
        $expiredStatus = $this->service->getCachedQueueWorkerStatus();
        $this->assertEquals(QueueWorkerStatus::STATUS_NOT_TESTED, $expiredStatus->status);
        $this->assertNull(Cache::get(QueueWorkerStatus::CACHE_KEY));
    }

    public function test_failed_status_caching_workflow()
    {
        // 1. Update to failed status
        $failedStatus = $this->service->updateQueueWorkerStatusFromJob('test_12345', false, null, 'Job execution failed');
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $failedStatus->status);
        $this->assertEquals('Job execution failed', $failedStatus->errorMessage);

        // 2. Verify failed status persists (doesn't expire automatically)
        $persistedStatus = $this->service->getCachedQueueWorkerStatus();
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $persistedStatus->status);
        $this->assertEquals('Job execution failed', $persistedStatus->errorMessage);
        $this->assertFalse($persistedStatus->isExpired()); // Failed statuses don't expire

        // 3. Manual cache invalidation
        $this->service->invalidateQueueWorkerStatus();
        $clearedStatus = $this->service->getCachedQueueWorkerStatus();
        $this->assertEquals(QueueWorkerStatus::STATUS_NOT_TESTED, $clearedStatus->status);
    }

    public function test_cache_invalidation_and_recovery()
    {
        // 1. Cache a successful status
        $successStatus = QueueWorkerStatus::completed(2.45, 'test_67890');
        $this->service->cacheQueueWorkerStatus($successStatus);

        // 2. Verify it's cached
        $cachedStatus = $this->service->getCachedQueueWorkerStatus();
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $cachedStatus->status);

        // 3. Invalidate cache
        $result = $this->service->invalidateQueueWorkerStatus();
        $this->assertTrue($result);

        // 4. Verify cache is cleared
        $clearedStatus = $this->service->getCachedQueueWorkerStatus();
        $this->assertEquals(QueueWorkerStatus::STATUS_NOT_TESTED, $clearedStatus->status);
    }

    public function test_cache_ttl_and_key_constants()
    {
        $this->assertEquals(3600, QueueWorkerStatus::CACHE_TTL); // 1 hour
        $this->assertEquals('setup_queue_worker_status', QueueWorkerStatus::CACHE_KEY);
    }

    public function test_status_array_serialization_roundtrip()
    {
        // Create a complex status with all fields
        $originalStatus = new QueueWorkerStatus(
            status: QueueWorkerStatus::STATUS_FAILED,
            message: 'Test failed with error',
            testCompletedAt: Carbon::now(),
            processingTime: null,
            errorMessage: 'Detailed error message',
            testJobId: 'test_complex_12345',
            troubleshooting: ['Step 1', 'Step 2', 'Step 3'],
            canRetry: true
        );

        // Convert to array and back
        $array = $originalStatus->toArray();
        $restoredStatus = QueueWorkerStatus::fromArray($array);

        // Verify all fields are preserved
        $this->assertEquals($originalStatus->status, $restoredStatus->status);
        $this->assertEquals($originalStatus->message, $restoredStatus->message);
        $this->assertEquals($originalStatus->errorMessage, $restoredStatus->errorMessage);
        $this->assertEquals($originalStatus->testJobId, $restoredStatus->testJobId);
        $this->assertEquals($originalStatus->troubleshooting, $restoredStatus->troubleshooting);
        $this->assertEquals($originalStatus->canRetry, $restoredStatus->canRetry);
        $this->assertEquals($originalStatus->testCompletedAt->toISOString(), $restoredStatus->testCompletedAt->toISOString());
    }
}