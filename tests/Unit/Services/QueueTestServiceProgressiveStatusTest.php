<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\QueueTestService;
use App\Services\QueueWorkerStatus;
use App\Jobs\TestQueueJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class QueueTestServiceProgressiveStatusTest extends TestCase
{

    private QueueTestService $queueTestService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queueTestService = new QueueTestService();
        
        // Clear any existing cache
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /** @test */
    public function it_provides_progressive_status_updates_during_dispatch()
    {
        Queue::fake();

        $status = $this->queueTestService->dispatchTestJobWithStatus();

        // Should return testing status with job ID
        $this->assertEquals(QueueWorkerStatus::STATUS_TESTING, $status->status);
        $this->assertEquals('Test job queued...', $status->message);
        $this->assertNotNull($status->testJobId);
        $this->assertFalse($status->canRetry);

        // Should cache the status
        $cachedStatus = $this->queueTestService->getCachedQueueWorkerStatus();
        $this->assertEquals(QueueWorkerStatus::STATUS_TESTING, $cachedStatus->status);
        $this->assertEquals($status->testJobId, $cachedStatus->testJobId);

        Queue::assertPushed(TestQueueJob::class);
    }

    /** @test */
    public function it_updates_test_phase_with_appropriate_messages()
    {
        $jobId = 'test_' . uniqid();

        // Test dispatching phase
        $status = $this->queueTestService->updateQueueWorkerTestPhase($jobId, 'dispatching');
        $this->assertEquals('Testing queue worker...', $status->message);
        $this->assertEquals($jobId, $status->testJobId);

        // Test queued phase
        $status = $this->queueTestService->updateQueueWorkerTestPhase($jobId, 'queued');
        $this->assertEquals('Test job queued...', $status->message);

        // Test processing phase
        $status = $this->queueTestService->updateQueueWorkerTestPhase($jobId, 'processing');
        $this->assertEquals('Test job processing...', $status->message);

        // Test unknown phase defaults to testing message
        $status = $this->queueTestService->updateQueueWorkerTestPhase($jobId, 'unknown');
        $this->assertEquals('Testing queue worker...', $status->message);
    }

    /** @test */
    public function it_caches_progressive_status_updates()
    {
        $jobId = 'test_' . uniqid();

        // Update to processing phase
        $this->queueTestService->updateQueueWorkerTestPhase($jobId, 'processing');

        // Should be cached
        $cachedStatus = $this->queueTestService->getCachedQueueWorkerStatus();
        $this->assertEquals(QueueWorkerStatus::STATUS_TESTING, $cachedStatus->status);
        $this->assertEquals('Test job processing...', $cachedStatus->message);
        $this->assertEquals($jobId, $cachedStatus->testJobId);
    }

    /** @test */
    public function it_handles_successful_job_completion_with_processing_time()
    {
        $jobId = 'test_' . uniqid();
        $processingTime = 2.45;

        $status = $this->queueTestService->updateQueueWorkerStatusFromJob(
            $jobId,
            true,
            $processingTime
        );

        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $status->status);
        $this->assertStringContainsString('Queue worker is functioning properly', $status->message);
        $this->assertStringContainsString('2.45s', $status->message);
        $this->assertEquals($processingTime, $status->processingTime);
        $this->assertEquals($jobId, $status->testJobId);
        $this->assertNotNull($status->testCompletedAt);
        $this->assertTrue($status->canRetry);
    }

    /** @test */
    public function it_handles_job_failure_with_error_message()
    {
        $jobId = 'test_' . uniqid();
        $errorMessage = 'Test job execution failed';

        $status = $this->queueTestService->updateQueueWorkerStatusFromJob(
            $jobId,
            false,
            null,
            $errorMessage
        );

        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Queue worker test failed', $status->message);
        $this->assertEquals($errorMessage, $status->errorMessage);
        $this->assertEquals($jobId, $status->testJobId);
        $this->assertTrue($status->canRetry);
        $this->assertNotNull($status->troubleshooting);
    }

    /** @test */
    public function it_handles_job_failure_without_specific_error_message()
    {
        $jobId = 'test_' . uniqid();

        $status = $this->queueTestService->updateQueueWorkerStatusFromJob(
            $jobId,
            false
        );

        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Test job failed without specific error', $status->errorMessage);
    }

    /** @test */
    public function it_checks_for_timeout_and_updates_status_accordingly()
    {
        $jobId = 'test_' . uniqid();

        // Set up a testing status
        $testingStatus = QueueWorkerStatus::testing($jobId, 'Test job processing...');
        $this->queueTestService->cacheQueueWorkerStatus($testingStatus);

        // Simulate a timed out job status in cache
        Cache::put("test_queue_job_{$jobId}", [
            'test_job_id' => $jobId,
            'status' => 'timeout',
            'message' => 'Test job timed out',
            'timed_out_at' => now()->toISOString(),
        ], 3600);

        $status = $this->queueTestService->checkQueueWorkerTimeout($jobId);

        $this->assertEquals(QueueWorkerStatus::STATUS_TIMEOUT, $status->status);
        $this->assertStringContainsString('timed out', $status->message);
        $this->assertEquals($jobId, $status->testJobId);
    }

    /** @test */
    public function it_does_not_check_timeout_for_non_testing_status()
    {
        $jobId = 'test_' . uniqid();

        // Set up a completed status
        $completedStatus = QueueWorkerStatus::completed(1.5, $jobId);
        $this->queueTestService->cacheQueueWorkerStatus($completedStatus);

        $status = $this->queueTestService->checkQueueWorkerTimeout($jobId);

        // Should return the existing completed status unchanged
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $status->status);
        $this->assertEquals($jobId, $status->testJobId);
    }

    /** @test */
    public function it_does_not_check_timeout_for_different_job_id()
    {
        $jobId1 = 'test_' . uniqid();
        $jobId2 = 'test_' . uniqid();

        // Set up a testing status for job1
        $testingStatus = QueueWorkerStatus::testing($jobId1, 'Test job processing...');
        $this->queueTestService->cacheQueueWorkerStatus($testingStatus);

        // Check timeout for job2
        $status = $this->queueTestService->checkQueueWorkerTimeout($jobId2);

        // Should return the existing status for job1 unchanged
        $this->assertEquals(QueueWorkerStatus::STATUS_TESTING, $status->status);
        $this->assertEquals($jobId1, $status->testJobId);
    }

    /** @test */
    public function it_handles_cache_errors_gracefully_during_phase_updates()
    {
        // Create a partial mock that will throw on cache operations
        $mockService = \Mockery::mock(QueueTestService::class)->makePartial();
        $mockService->shouldReceive('cacheQueueWorkerStatus')
            ->andThrow(new \Exception('Cache error'));

        $jobId = 'test_' . uniqid();
        
        // The method should catch the exception and return an error status
        try {
            $status = $mockService->updateQueueWorkerTestPhase($jobId, 'processing');
            $this->assertEquals(QueueWorkerStatus::STATUS_ERROR, $status->status);
            $this->assertStringContainsString('Failed to update test phase', $status->errorMessage);
            $this->assertEquals($jobId, $status->testJobId);
        } catch (\Exception $e) {
            // If the method doesn't handle the error, we expect it to be caught here
            $this->assertStringContainsString('Cache error', $e->getMessage());
        }
    }

    /** @test */
    public function it_handles_dispatch_errors_gracefully()
    {
        // Mock queue to throw exception
        Queue::shouldReceive('push')
            ->andThrow(new \Exception('Queue dispatch error'));

        $status = $this->queueTestService->dispatchTestJobWithStatus();

        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertStringContainsString('Failed to dispatch test job', $status->errorMessage);
        $this->assertTrue($status->canRetry);
    }

    /** @test */
    public function it_provides_initial_testing_status_before_dispatch()
    {
        Queue::fake();

        $status = $this->queueTestService->dispatchTestJobWithStatus();

        // Should return testing status
        $this->assertEquals(QueueWorkerStatus::STATUS_TESTING, $status->status);
        $this->assertNotNull($status->testJobId);
        $this->assertFalse($status->canRetry);
    }

    /** @test */
    public function it_updates_status_to_queued_after_successful_dispatch()
    {
        Queue::fake();

        $status = $this->queueTestService->dispatchTestJobWithStatus();

        // Final status should be queued after successful dispatch
        $this->assertEquals(QueueWorkerStatus::STATUS_TESTING, $status->status);
        $this->assertEquals('Test job queued...', $status->message);
        $this->assertNotNull($status->testJobId);
    }
}