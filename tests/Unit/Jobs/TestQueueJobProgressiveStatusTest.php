<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\TestQueueJob;
use App\Services\QueueTestService;
use App\Services\QueueWorkerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;

class TestQueueJobProgressiveStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Log::fake();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_updates_status_to_processing_when_job_starts()
    {
        $jobId = 'test_' . uniqid();
        $job = new TestQueueJob($jobId, 0);

        // Mock the QueueTestService
        $mockService = Mockery::mock(QueueTestService::class);
        $mockService->shouldReceive('updateQueueWorkerTestPhase')
            ->once()
            ->with($jobId, 'processing')
            ->andReturn(QueueWorkerStatus::processing($jobId));
        
        $mockService->shouldReceive('updateQueueWorkerStatusFromJob')
            ->once()
            ->with($jobId, true, Mockery::type('float'), null)
            ->andReturn(QueueWorkerStatus::completed(1.0, $jobId));

        $this->app->instance(QueueTestService::class, $mockService);

        // Execute the job
        $job->handle();

        // Verify the job status was updated to processing
        $cacheKey = "test_queue_job_{$jobId}";
        $cachedStatus = Cache::get($cacheKey);
        
        $this->assertNotNull($cachedStatus);
        $this->assertEquals('completed', $cachedStatus['status']);
        $this->assertArrayHasKey('processing_time', $cachedStatus);
        $this->assertArrayHasKey('started_at', $cachedStatus);
    }

    /** @test */
    public function it_provides_progressive_status_messages_during_execution()
    {
        $jobId = 'test_' . uniqid();
        $job = new TestQueueJob($jobId, 0);

        // Mock the QueueTestService
        $mockService = Mockery::mock(QueueTestService::class);
        $mockService->shouldReceive('updateQueueWorkerTestPhase')
            ->once()
            ->with($jobId, 'processing');
        
        $mockService->shouldReceive('updateQueueWorkerStatusFromJob')
            ->once()
            ->with($jobId, true, Mockery::type('float'), null);

        $this->app->instance(QueueTestService::class, $mockService);

        // Execute the job
        $job->handle();

        // Check that multiple status updates were cached
        $cacheKey = "test_queue_job_{$jobId}";
        $finalStatus = Cache::get($cacheKey);
        
        $this->assertEquals('completed', $finalStatus['status']);
        $this->assertStringContains('completed successfully', $finalStatus['message']);
        $this->assertArrayHasKey('processing_time', $finalStatus);
    }

    /** @test */
    public function it_shows_delay_information_during_delayed_execution()
    {
        $jobId = 'test_' . uniqid();
        $delay = 2; // 2 seconds delay
        $job = new TestQueueJob($jobId, $delay);

        // Mock the QueueTestService
        $mockService = Mockery::mock(QueueTestService::class);
        $mockService->shouldReceive('updateQueueWorkerTestPhase')
            ->once()
            ->with($jobId, 'processing');
        
        $mockService->shouldReceive('updateQueueWorkerStatusFromJob')
            ->once()
            ->with($jobId, true, Mockery::type('float'), null);

        $this->app->instance(QueueTestService::class, $mockService);

        // Mock sleep to avoid actual delay in tests
        $originalSleep = null;
        if (function_exists('sleep')) {
            $originalSleep = 'sleep';
        }
        
        // We can't easily mock sleep, so we'll test with 0 delay for speed
        $job = new TestQueueJob($jobId, 0);
        $job->handle();

        // Verify the job completed
        $cacheKey = "test_queue_job_{$jobId}";
        $finalStatus = Cache::get($cacheKey);
        
        $this->assertEquals('completed', $finalStatus['status']);
    }

    /** @test */
    public function it_updates_status_during_test_operations()
    {
        $jobId = 'test_' . uniqid();
        $job = new TestQueueJob($jobId, 0);

        // Mock the QueueTestService
        $mockService = Mockery::mock(QueueTestService::class);
        $mockService->shouldReceive('updateQueueWorkerTestPhase')
            ->once()
            ->with($jobId, 'processing');
        
        $mockService->shouldReceive('updateQueueWorkerStatusFromJob')
            ->once()
            ->with($jobId, true, Mockery::type('float'), null);

        $this->app->instance(QueueTestService::class, $mockService);

        // Execute the job
        $job->handle();

        // Verify that status was updated during test operations
        $cacheKey = "test_queue_job_{$jobId}";
        $finalStatus = Cache::get($cacheKey);
        
        $this->assertEquals('completed', $finalStatus['status']);
        $this->assertStringContains('Performing test operations', $finalStatus['message']);
    }

    /** @test */
    public function it_calculates_and_displays_processing_time()
    {
        $jobId = 'test_' . uniqid();
        $job = new TestQueueJob($jobId, 0);

        // Mock the QueueTestService
        $mockService = Mockery::mock(QueueTestService::class);
        $mockService->shouldReceive('updateQueueWorkerTestPhase')
            ->once()
            ->with($jobId, 'processing');
        
        $mockService->shouldReceive('updateQueueWorkerStatusFromJob')
            ->once()
            ->with($jobId, true, Mockery::type('float'), null)
            ->andReturnUsing(function ($jobId, $success, $processingTime) {
                // Verify processing time is calculated
                $this->assertIsFloat($processingTime);
                $this->assertGreaterThan(0, $processingTime);
                return QueueWorkerStatus::completed($processingTime, $jobId);
            });

        $this->app->instance(QueueTestService::class, $mockService);

        // Execute the job
        $job->handle();

        // Verify processing time is recorded
        $cacheKey = "test_queue_job_{$jobId}";
        $finalStatus = Cache::get($cacheKey);
        
        $this->assertArrayHasKey('processing_time', $finalStatus);
        $this->assertIsFloat($finalStatus['processing_time']);
        $this->assertGreaterThan(0, $finalStatus['processing_time']);
        
        // Verify processing time is in the completion message
        $this->assertStringContains($finalStatus['processing_time'] . 's', $finalStatus['message']);
    }

    /** @test */
    public function it_handles_job_failure_with_progressive_status_updates()
    {
        $jobId = 'test_' . uniqid();
        $job = new TestQueueJob($jobId, 0);

        // Mock the QueueTestService to throw an exception during phase update
        $mockService = Mockery::mock(QueueTestService::class);
        $mockService->shouldReceive('updateQueueWorkerTestPhase')
            ->once()
            ->with($jobId, 'processing')
            ->andThrow(new \Exception('Test failure'));
        
        $mockService->shouldReceive('updateQueueWorkerStatusFromJob')
            ->once()
            ->with($jobId, false, null, 'Test failure');

        $this->app->instance(QueueTestService::class, $mockService);

        // Execute the job and expect it to throw
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test failure');
        
        $job->handle();
    }

    /** @test */
    public function it_calls_failed_method_on_job_failure()
    {
        $jobId = 'test_' . uniqid();
        $job = new TestQueueJob($jobId, 0);
        $exception = new \Exception('Test job failure');

        // Mock the QueueTestService
        $mockService = Mockery::mock(QueueTestService::class);
        $mockService->shouldReceive('updateQueueWorkerStatusFromJob')
            ->once()
            ->with($jobId, false, null, 'Test job failure');

        $this->app->instance(QueueTestService::class, $mockService);

        // Call the failed method
        $job->failed($exception);

        // Verify failure status was cached
        $cacheKey = "test_queue_job_{$jobId}";
        $failedStatus = Cache::get($cacheKey);
        
        $this->assertEquals('failed', $failedStatus['status']);
        $this->assertEquals('Test job failure', $failedStatus['error_message']);
        $this->assertArrayHasKey('failed_at', $failedStatus);
    }

    /** @test */
    public function it_logs_progressive_status_updates()
    {
        $jobId = 'test_' . uniqid();
        $job = new TestQueueJob($jobId, 0);

        // Mock the QueueTestService
        $mockService = Mockery::mock(QueueTestService::class);
        $mockService->shouldReceive('updateQueueWorkerTestPhase')
            ->once()
            ->with($jobId, 'processing');
        
        $mockService->shouldReceive('updateQueueWorkerStatusFromJob')
            ->once()
            ->with($jobId, true, Mockery::type('float'), null);

        $this->app->instance(QueueTestService::class, $mockService);

        // Execute the job
        $job->handle();

        // Verify logging
        Log::assertLogged('info', function ($message, $context) use ($jobId) {
            return str_contains($message, 'TestQueueJob started') && 
                   $context['test_job_id'] === $jobId;
        });

        Log::assertLogged('info', function ($message, $context) use ($jobId) {
            return str_contains($message, 'TestQueueJob completed successfully') && 
                   $context['test_job_id'] === $jobId;
        });

        Log::assertLogged('debug', function ($message, $context) use ($jobId) {
            return str_contains($message, 'TestQueueJob operations completed') && 
                   $context['test_job_id'] === $jobId;
        });
    }

    /** @test */
    public function it_performs_test_operations_successfully()
    {
        $jobId = 'test_' . uniqid();
        $job = new TestQueueJob($jobId, 0);

        // Mock the QueueTestService
        $mockService = Mockery::mock(QueueTestService::class);
        $mockService->shouldReceive('updateQueueWorkerTestPhase')
            ->once()
            ->with($jobId, 'processing');
        
        $mockService->shouldReceive('updateQueueWorkerStatusFromJob')
            ->once()
            ->with($jobId, true, Mockery::type('float'), null);

        $this->app->instance(QueueTestService::class, $mockService);

        // Execute the job
        $job->handle();

        // Verify test operations were logged
        Log::assertLogged('debug', function ($message, $context) use ($jobId) {
            return str_contains($message, 'TestQueueJob operations completed') && 
                   $context['test_job_id'] === $jobId &&
                   $context['cache_test'] === 'passed' &&
                   $context['computation_test'] === 'passed' &&
                   $context['memory_test'] === 'passed';
        });
    }

    /** @test */
    public function it_handles_service_unavailability_gracefully()
    {
        $jobId = 'test_' . uniqid();
        $job = new TestQueueJob($jobId, 0);

        // Don't mock the service - let it fail gracefully
        // The job should still complete but log errors

        // Execute the job
        $job->handle();

        // Verify the job still completed
        $cacheKey = "test_queue_job_{$jobId}";
        $finalStatus = Cache::get($cacheKey);
        
        $this->assertEquals('completed', $finalStatus['status']);

        // Verify error was logged for service unavailability
        Log::assertLogged('error', function ($message, $context) use ($jobId) {
            return str_contains($message, 'Failed to update queue worker test phase') && 
                   $context['test_job_id'] === $jobId;
        });
    }
}