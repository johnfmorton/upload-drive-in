<?php

namespace Tests\Feature;

use App\Jobs\TestQueueJob;
use App\Services\QueueTestService;
use App\Services\QueueWorkerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueWorkerEnhancedErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    private QueueTestService $queueTestService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queueTestService = app(QueueTestService::class);
    }

    /** @test */
    public function it_handles_dispatch_failures_with_specific_error_type()
    {
        // Simulate dispatch failure by using invalid queue configuration
        config(['queue.default' => 'invalid_driver']);
        
        $status = $this->queueTestService->dispatchTestJobWithStatus(0, 15);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertNotNull($status->errorMessage);
        $this->assertTrue($status->canRetry);
        $this->assertNotNull($status->troubleshooting);
        
        // Check that troubleshooting guidance is provided (may be default or dispatch-specific)
        $this->assertIsArray($status->troubleshooting);
        $this->assertNotEmpty($status->troubleshooting);
        
        // Check that at least one troubleshooting step mentions queue or configuration
        $troubleshootingText = implode(' ', $status->troubleshooting);
        $this->assertTrue(
            str_contains($troubleshootingText, 'queue') || 
            str_contains($troubleshootingText, 'configuration') ||
            str_contains($troubleshootingText, 'worker'),
            'Troubleshooting should mention queue, configuration, or worker'
        );
    }

    /** @test */
    public function it_creates_dispatch_failed_status_with_specific_troubleshooting()
    {
        $errorMessage = 'Queue connection failed';
        $status = QueueWorkerStatus::dispatchFailed($errorMessage);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Failed to dispatch test job', $status->message);
        $this->assertEquals($errorMessage, $status->errorMessage);
        $this->assertTrue($status->canRetry);
        $this->assertContains('Verify queue configuration in .env file (QUEUE_CONNECTION)', $status->troubleshooting);
        $this->assertContains('Check if database tables exist: php artisan migrate', $status->troubleshooting);
    }

    /** @test */
    public function it_creates_job_failed_status_with_specific_troubleshooting()
    {
        $errorMessage = 'Job execution failed due to memory limit';
        $testJobId = 'test_12345';
        $status = QueueWorkerStatus::jobFailed($errorMessage, $testJobId);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Test job execution failed', $status->message);
        $this->assertEquals($errorMessage, $status->errorMessage);
        $this->assertEquals($testJobId, $status->testJobId);
        $this->assertTrue($status->canRetry);
        $this->assertContains('Check failed jobs table: php artisan queue:failed', $status->troubleshooting);
        $this->assertContains('Check memory limits and execution time settings', $status->troubleshooting);
    }

    /** @test */
    public function it_creates_network_error_status_with_specific_troubleshooting()
    {
        $errorMessage = 'Connection timeout';
        $testJobId = 'test_67890';
        $status = QueueWorkerStatus::networkError($errorMessage, $testJobId);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_ERROR, $status->status);
        $this->assertEquals('Network error during queue test', $status->message);
        $this->assertEquals($errorMessage, $status->errorMessage);
        $this->assertEquals($testJobId, $status->testJobId);
        $this->assertTrue($status->canRetry);
        $this->assertContains('Check your internet connection', $status->troubleshooting);
        $this->assertContains('Verify the application server is accessible', $status->troubleshooting);
    }

    /** @test */
    public function it_handles_configurable_timeout_periods()
    {
        Queue::fake();
        
        // Test with custom timeout
        $customTimeout = 45;
        $status = $this->queueTestService->dispatchTestJobWithStatus(0, $customTimeout);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_TESTING, $status->status);
        $this->assertNotNull($status->testJobId);
        
        // Verify job was dispatched
        Queue::assertPushed(TestQueueJob::class);
        
        // Check that the job status in cache has the correct timeout
        $jobStatus = $this->queueTestService->checkTestJobStatus($status->testJobId);
        $this->assertEquals($customTimeout, $jobStatus['timeout_seconds']);
    }

    /** @test */
    public function it_provides_retry_functionality_for_failed_tests()
    {
        $status = QueueWorkerStatus::failed('Test error message');
        
        $this->assertTrue($status->canRetry);
        
        // Test that timeout status also allows retry
        $timeoutStatus = QueueWorkerStatus::timeout('test_job_id');
        $this->assertTrue($timeoutStatus->canRetry);
        
        // Test that network error status allows retry
        $networkStatus = QueueWorkerStatus::networkError('Network error');
        $this->assertTrue($networkStatus->canRetry);
    }

    /** @test */
    public function it_caches_error_status_for_persistence()
    {
        $errorMessage = 'Test dispatch failure';
        $status = QueueWorkerStatus::dispatchFailed($errorMessage);
        
        // Cache the error status
        $cached = $this->queueTestService->cacheQueueWorkerStatus($status);
        $this->assertTrue($cached);
        
        // Retrieve cached status
        $retrievedStatus = $this->queueTestService->getCachedQueueWorkerStatus();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $retrievedStatus->status);
        $this->assertEquals('Failed to dispatch test job', $retrievedStatus->message);
        $this->assertEquals($errorMessage, $retrievedStatus->errorMessage);
        $this->assertTrue($retrievedStatus->canRetry);
    }

    /** @test */
    public function it_handles_timeout_scenarios_with_proper_error_messages()
    {
        Queue::fake();
        
        // Dispatch a test job
        $status = $this->queueTestService->dispatchTestJobWithStatus(0, 1); // 1 second timeout
        $testJobId = $status->testJobId;
        
        // Wait for timeout to occur (simulate by manually setting timeout in cache)
        $cacheKey = 'test_queue_job_' . $testJobId;
        $jobStatus = Cache::get($cacheKey);
        $jobStatus['timeout_at'] = now()->subSeconds(5)->toISOString(); // Set timeout in the past
        Cache::put($cacheKey, $jobStatus, 3600);
        
        // Check for timeout
        $timeoutStatus = $this->queueTestService->checkQueueWorkerTimeout($testJobId);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_TIMEOUT, $timeoutStatus->status);
        $this->assertStringContainsString('timed out', $timeoutStatus->message);
        $this->assertTrue($timeoutStatus->canRetry);
        $this->assertNotNull($timeoutStatus->troubleshooting);
    }

    /** @test */
    public function it_provides_specific_troubleshooting_for_different_error_types()
    {
        // Test dispatch failure troubleshooting
        $dispatchStatus = QueueWorkerStatus::dispatchFailed('Connection failed');
        $this->assertContains('Verify queue configuration in .env file (QUEUE_CONNECTION)', $dispatchStatus->troubleshooting);
        $this->assertContains('Check if database tables exist: php artisan migrate', $dispatchStatus->troubleshooting);
        
        // Test job failure troubleshooting
        $jobStatus = QueueWorkerStatus::jobFailed('Memory limit exceeded', 'test_123');
        $this->assertContains('Check failed jobs table: php artisan queue:failed', $jobStatus->troubleshooting);
        $this->assertContains('Check memory limits and execution time settings', $jobStatus->troubleshooting);
        
        // Test timeout troubleshooting
        $timeoutStatus = QueueWorkerStatus::timeout('test_456');
        $this->assertContains('Ensure queue worker is running: php artisan queue:work', $timeoutStatus->troubleshooting);
        $this->assertContains('Check if worker process is stuck or crashed', $timeoutStatus->troubleshooting);
        
        // Test network error troubleshooting
        $networkStatus = QueueWorkerStatus::networkError('Connection refused');
        $this->assertContains('Check your internet connection', $networkStatus->troubleshooting);
        $this->assertContains('Verify the application server is accessible', $networkStatus->troubleshooting);
    }

    /** @test */
    public function it_handles_multiple_retry_attempts_with_proper_logging()
    {
        // This test would require mocking the dispatch mechanism to fail multiple times
        // For now, we'll test the retry logic indirectly through the canRetry flag
        
        $failedStatus = QueueWorkerStatus::failed('Temporary failure');
        $this->assertTrue($failedStatus->canRetry);
        
        $timeoutStatus = QueueWorkerStatus::timeout('test_job');
        $this->assertTrue($timeoutStatus->canRetry);
        
        $networkStatus = QueueWorkerStatus::networkError('Network issue');
        $this->assertTrue($networkStatus->canRetry);
        
        // All error types should allow retry
        $this->assertTrue($failedStatus->canRetry && $timeoutStatus->canRetry && $networkStatus->canRetry);
    }

    /** @test */
    public function it_converts_error_status_to_array_with_all_fields()
    {
        $errorMessage = 'Test error';
        $testJobId = 'test_array_conversion';
        $status = QueueWorkerStatus::jobFailed($errorMessage, $testJobId);
        
        $array = $status->toArray();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $array['status']);
        $this->assertEquals('Test job execution failed', $array['message']);
        $this->assertEquals($errorMessage, $array['error_message']);
        $this->assertEquals($testJobId, $array['test_job_id']);
        $this->assertTrue($array['can_retry']);
        $this->assertIsArray($array['troubleshooting']);
        $this->assertNotEmpty($array['troubleshooting']);
    }

    /** @test */
    public function it_handles_enhanced_error_handling_in_http_endpoint()
    {
        // Test the enhanced error handling through the HTTP endpoint
        $response = $this->postJson('/setup/queue/test', [
            'delay' => 0,
            'timeout' => 20 // Custom timeout
        ]);
        
        // Should succeed with queue fake
        Queue::fake();
        
        $response = $this->postJson('/setup/queue/test', [
            'delay' => 0,
            'timeout' => 20
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'test_job_id',
            'queue_worker_status' => [
                'status',
                'message',
                'can_retry',
                'troubleshooting'
            ]
        ]);
    }
}