<?php

namespace Tests\Unit\Services;

use App\Services\QueueTestService;
use App\Services\QueueWorkerTestSecurityService;
use App\Services\QueueWorkerPerformanceService;
use App\Services\QueueWorkerStatus;
use App\Jobs\TestQueueJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;
use Tests\TestCase;
use Mockery;

/**
 * Comprehensive unit tests for QueueTestService.
 * 
 * Tests all methods including caching, error handling, and status management.
 */
class QueueTestServiceComprehensiveTest extends TestCase
{
    private QueueTestService $service;
    private $mockSecurityService;
    private $mockPerformanceService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockSecurityService = Mockery::mock(QueueWorkerTestSecurityService::class);
        $this->mockPerformanceService = Mockery::mock(QueueWorkerPerformanceService::class);
        
        $this->service = new QueueTestService(
            $this->mockSecurityService,
            $this->mockPerformanceService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Test successful test job dispatch and caching.
     * 
     * @covers QueueTestService::dispatchTestJob
     */
    public function test_dispatch_test_job_success()
    {
        Queue::fake();
        
        $this->mockSecurityService
            ->shouldReceive('validateTestRequest')
            ->once()
            ->andReturn(true);
            
        $this->mockPerformanceService
            ->shouldReceive('recordTestStart')
            ->once();

        $result = $this->service->dispatchTestJob();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('job_id', $result);
        $this->assertArrayHasKey('message', $result);
        
        Queue::assertPushed(TestQueueJob::class);
        
        // Verify caching
        $cachedStatus = Cache::get(QueueWorkerStatus::CACHE_KEY);
        $this->assertEquals(QueueWorkerStatus::STATUS_TESTING, $cachedStatus['status']);
    }

    /**
     * Test test job dispatch failure handling.
     * 
     * @covers QueueTestService::dispatchTestJob
     */
    public function test_dispatch_test_job_failure()
    {
        $this->mockSecurityService
            ->shouldReceive('validateTestRequest')
            ->once()
            ->andThrow(new \Exception('Security validation failed'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to dispatch test queue job', Mockery::type('array'));

        $result = $this->service->dispatchTestJob();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to dispatch test job', $result['message']);
        
        // Verify error status is cached
        $cachedStatus = Cache::get(QueueWorkerStatus::CACHE_KEY);
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $cachedStatus['status']);
    }

    /**
     * Test getting test job status for completed job.
     * 
     * @covers QueueTestService::getTestJobStatus
     */
    public function test_get_test_job_status_completed()
    {
        $jobId = 'test-job-123';
        $completedAt = Carbon::now();
        $processingTime = 2.5;
        
        // Mock completed job data in cache
        Cache::put("test_queue_job_{$jobId}", [
            'status' => 'completed',
            'completed_at' => $completedAt->toISOString(),
            'processing_time' => $processingTime,
            'message' => 'Test job completed successfully'
        ], 3600);

        $result = $this->service->getTestJobStatus($jobId);

        $this->assertEquals('completed', $result['status']);
        $this->assertEquals($processingTime, $result['processing_time']);
        $this->assertStringContainsString('completed successfully', $result['message']);
    }

    /**
     * Test getting test job status for failed job.
     * 
     * @covers QueueTestService::getTestJobStatus
     */
    public function test_get_test_job_status_failed()
    {
        $jobId = 'test-job-456';
        $errorMessage = 'Job processing failed';
        
        Cache::put("test_queue_job_{$jobId}", [
            'status' => 'failed',
            'error_message' => $errorMessage,
            'failed_at' => Carbon::now()->toISOString()
        ], 3600);

        $result = $this->service->getTestJobStatus($jobId);

        $this->assertEquals('failed', $result['status']);
        $this->assertEquals($errorMessage, $result['error_message']);
        $this->assertArrayHasKey('troubleshooting', $result);
    }

    /**
     * Test getting test job status for non-existent job.
     * 
     * @covers QueueTestService::getTestJobStatus
     */
    public function test_get_test_job_status_not_found()
    {
        $result = $this->service->getTestJobStatus('non-existent-job');

        $this->assertEquals('not_found', $result['status']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    /**
     * Test marking test job as completed.
     * 
     * @covers QueueTestService::markTestJobCompleted
     */
    public function test_mark_test_job_completed()
    {
        $jobId = 'test-job-789';
        $processingTime = 1.23;
        
        $this->mockPerformanceService
            ->shouldReceive('recordTestCompletion')
            ->once()
            ->with($jobId, $processingTime);

        $this->service->markTestJobCompleted($jobId, $processingTime);

        // Verify job status is cached
        $cachedJob = Cache::get("test_queue_job_{$jobId}");
        $this->assertEquals('completed', $cachedJob['status']);
        $this->assertEquals($processingTime, $cachedJob['processing_time']);
        
        // Verify queue worker status is updated
        $cachedStatus = Cache::get(QueueWorkerStatus::CACHE_KEY);
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $cachedStatus['status']);
    }

    /**
     * Test marking test job as failed.
     * 
     * @covers QueueTestService::markTestJobFailed
     */
    public function test_mark_test_job_failed()
    {
        $jobId = 'test-job-failed';
        $errorMessage = 'Processing error occurred';
        
        $this->mockPerformanceService
            ->shouldReceive('recordTestFailure')
            ->once()
            ->with($jobId, $errorMessage);

        $this->service->markTestJobFailed($jobId, $errorMessage);

        // Verify job status is cached
        $cachedJob = Cache::get("test_queue_job_{$jobId}");
        $this->assertEquals('failed', $cachedJob['status']);
        $this->assertEquals($errorMessage, $cachedJob['error_message']);
        
        // Verify queue worker status is updated
        $cachedStatus = Cache::get(QueueWorkerStatus::CACHE_KEY);
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $cachedStatus['status']);
    }

    /**
     * Test getting cached queue worker status.
     * 
     * @covers QueueTestService::getCachedQueueWorkerStatus
     */
    public function test_get_cached_queue_worker_status()
    {
        $status = QueueWorkerStatus::createCompleted(
            'Queue worker is functioning properly',
            Carbon::now(),
            2.5
        );
        
        Cache::put(QueueWorkerStatus::CACHE_KEY, $status->toArray(), QueueWorkerStatus::CACHE_TTL);

        $result = $this->service->getCachedQueueWorkerStatus();

        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $result['status']);
        $this->assertEquals(2.5, $result['processing_time']);
    }

    /**
     * Test getting cached queue worker status when none exists.
     * 
     * @covers QueueTestService::getCachedQueueWorkerStatus
     */
    public function test_get_cached_queue_worker_status_not_found()
    {
        Cache::forget(QueueWorkerStatus::CACHE_KEY);

        $result = $this->service->getCachedQueueWorkerStatus();

        $this->assertEquals(QueueWorkerStatus::STATUS_NOT_TESTED, $result['status']);
        $this->assertStringContainsString('Click the Test Queue Worker button', $result['message']);
    }

    /**
     * Test queue health monitoring.
     * 
     * @covers QueueTestService::getQueueHealth
     */
    public function test_get_queue_health()
    {
        DB::shouldReceive('table')
            ->with('jobs')
            ->andReturnSelf();
            
        DB::shouldReceive('count')
            ->andReturn(5);
            
        DB::shouldReceive('table')
            ->with('failed_jobs')
            ->andReturnSelf();
            
        DB::shouldReceive('count')
            ->andReturn(2);

        $this->mockPerformanceService
            ->shouldReceive('getQueueMetrics')
            ->once()
            ->andReturn([
                'average_processing_time' => 1.5,
                'success_rate' => 95.0
            ]);

        $result = $this->service->getQueueHealth();

        $this->assertArrayHasKey('pending_jobs', $result);
        $this->assertArrayHasKey('failed_jobs', $result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertEquals(5, $result['pending_jobs']);
        $this->assertEquals(2, $result['failed_jobs']);
    }

    /**
     * Test cleanup of old test jobs.
     * 
     * @covers QueueTestService::cleanupOldTestJobs
     */
    public function test_cleanup_old_test_jobs()
    {
        // Create some old test job cache entries
        $oldJobId1 = 'old-job-1';
        $oldJobId2 = 'old-job-2';
        $recentJobId = 'recent-job';
        
        Cache::put("test_queue_job_{$oldJobId1}", [
            'status' => 'completed',
            'completed_at' => Carbon::now()->subDays(2)->toISOString()
        ], 3600);
        
        Cache::put("test_queue_job_{$oldJobId2}", [
            'status' => 'failed',
            'failed_at' => Carbon::now()->subDays(3)->toISOString()
        ], 3600);
        
        Cache::put("test_queue_job_{$recentJobId}", [
            'status' => 'completed',
            'completed_at' => Carbon::now()->subHours(1)->toISOString()
        ], 3600);
        
        // Mock job index
        Cache::put('test_queue_job_index', [$oldJobId1, $oldJobId2, $recentJobId], 3600);

        $this->mockPerformanceService
            ->shouldReceive('recordCleanupOperation')
            ->once();

        $result = $this->service->cleanupOldTestJobs();

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['cleaned_count']);
        
        // Verify old jobs are removed
        $this->assertNull(Cache::get("test_queue_job_{$oldJobId1}"));
        $this->assertNull(Cache::get("test_queue_job_{$oldJobId2}"));
        
        // Verify recent job is kept
        $this->assertNotNull(Cache::get("test_queue_job_{$recentJobId}"));
    }

    /**
     * Test timeout handling for test jobs.
     * 
     * @covers QueueTestService::handleTestTimeout
     */
    public function test_handle_test_timeout()
    {
        $jobId = 'timeout-job';
        
        // Create a job that's been running too long
        Cache::put("test_queue_job_{$jobId}", [
            'status' => 'processing',
            'started_at' => Carbon::now()->subMinutes(2)->toISOString()
        ], 3600);

        $this->mockPerformanceService
            ->shouldReceive('recordTestTimeout')
            ->once()
            ->with($jobId);

        $this->service->handleTestTimeout($jobId);

        // Verify job is marked as timed out
        $cachedJob = Cache::get("test_queue_job_{$jobId}");
        $this->assertEquals('timeout', $cachedJob['status']);
        
        // Verify queue worker status is updated
        $cachedStatus = Cache::get(QueueWorkerStatus::CACHE_KEY);
        $this->assertEquals(QueueWorkerStatus::STATUS_TIMEOUT, $cachedStatus['status']);
    }

    /**
     * Test error handling in queue health check.
     * 
     * @covers QueueTestService::getQueueHealth
     */
    public function test_get_queue_health_error_handling()
    {
        DB::shouldReceive('table')
            ->with('jobs')
            ->andThrow(new \Exception('Database connection failed'));

        Log::shouldReceive('error')
            ->once()
            ->with('Error getting queue health', Mockery::type('array'));

        $result = $this->service->getQueueHealth();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Error retrieving queue health', $result['message']);
    }

    /**
     * Test cache invalidation for queue worker status.
     * 
     * @covers QueueTestService::invalidateQueueWorkerStatusCache
     */
    public function test_invalidate_queue_worker_status_cache()
    {
        // Set up cached status
        $status = QueueWorkerStatus::createCompleted('Test message', Carbon::now(), 1.0);
        Cache::put(QueueWorkerStatus::CACHE_KEY, $status->toArray(), QueueWorkerStatus::CACHE_TTL);
        
        $this->assertNotNull(Cache::get(QueueWorkerStatus::CACHE_KEY));

        $this->service->invalidateQueueWorkerStatusCache();

        $this->assertNull(Cache::get(QueueWorkerStatus::CACHE_KEY));
    }

    /**
     * Test progressive status updates during job processing.
     * 
     * @covers QueueTestService::updateJobProgress
     */
    public function test_update_job_progress()
    {
        $jobId = 'progress-job';
        $progressMessage = 'Job is 50% complete';
        
        // Set up initial job
        Cache::put("test_queue_job_{$jobId}", [
            'status' => 'processing',
            'started_at' => Carbon::now()->toISOString()
        ], 3600);

        $this->service->updateJobProgress($jobId, $progressMessage);

        $cachedJob = Cache::get("test_queue_job_{$jobId}");
        $this->assertEquals('processing', $cachedJob['status']);
        $this->assertEquals($progressMessage, $cachedJob['progress_message']);
        $this->assertArrayHasKey('last_updated', $cachedJob);
    }

    /**
     * Test rate limiting integration.
     * 
     * @covers QueueTestService::dispatchTestJob
     */
    public function test_dispatch_test_job_rate_limiting()
    {
        $this->mockSecurityService
            ->shouldReceive('validateTestRequest')
            ->once()
            ->andThrow(new \Exception('Rate limit exceeded'));

        $result = $this->service->dispatchTestJob();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Rate limit', $result['message']);
    }
}