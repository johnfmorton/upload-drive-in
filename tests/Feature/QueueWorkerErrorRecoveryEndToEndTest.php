<?php

namespace Tests\Feature;

use App\Services\QueueTestService;
use App\Services\QueueWorkerStatus;
use App\Jobs\TestQueueJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * End-to-end tests for all error scenarios and recovery mechanisms.
 * 
 * Tests comprehensive error handling, recovery workflows, and user guidance
 * for various failure conditions in the queue worker status system.
 */
class QueueWorkerErrorRecoveryEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Test complete error recovery workflow from dispatch failure to successful retry.
     * 
     * @covers End-to-end error recovery
     */
    public function test_complete_error_recovery_workflow()
    {
        // Step 1: Simulate dispatch failure
        Queue::fake();
        Queue::shouldReceive('push')->once()->andThrow(new \Exception('Queue connection failed'));
        
        $response = $this->post('/setup/queue-worker/test');
        
        $response->assertOk();
        $data = $response->json();
        
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Failed to dispatch test job', $data['message']);
        
        // Verify error status is cached with troubleshooting info
        $cachedStatus = Cache::get(QueueWorkerStatus::CACHE_KEY);
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $cachedStatus['status']);
        $this->assertTrue($cachedStatus['can_retry']);
        $this->assertArrayHasKey('troubleshooting', $cachedStatus);

        // Step 2: Check status endpoint shows error with guidance
        $response = $this->get('/setup/queue-worker/status');
        
        $response->assertOk();
        $data = $response->json();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $data['status']);
        $this->assertArrayHasKey('troubleshooting', $data);
        $this->assertContains('Check queue configuration', implode(' ', $data['troubleshooting']));

        // Step 3: Retry after fixing the issue
        Queue::fake(); // Reset queue fake to allow successful dispatch
        
        $response = $this->post('/setup/queue-worker/test');
        
        $response->assertOk();
        $data = $response->json();
        
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('job_id', $data);
        
        Queue::assertPushed(TestQueueJob::class);

        // Step 4: Complete the retry successfully
        $jobId = $data['job_id'];
        app(QueueTestService::class)->markTestJobCompleted($jobId, 1.5);
        
        $response = $this->get('/setup/queue-worker/status');
        $data = $response->json();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $data['status']);
        $this->assertStringContainsString('functioning properly', $data['message']);
    }

    /**
     * Test timeout scenario with progressive status updates and recovery.
     * 
     * @covers Timeout handling and recovery
     */
    public function test_timeout_scenario_with_recovery()
    {
        Queue::fake();
        
        // Step 1: Start test successfully
        $response = $this->post('/setup/queue-worker/test');
        $jobId = $response->json()['job_id'];
        
        // Step 2: Simulate progressive status updates
        app(QueueTestService::class)->updateJobProgress($jobId, 'Test job queued, waiting for worker...');
        
        $response = $this->get("/setup/queue-worker/test/{$jobId}/status");
        $data = $response->json();
        
        $this->assertEquals('processing', $data['status']);
        $this->assertStringContainsString('waiting for worker', $data['progress_message']);

        // Step 3: Simulate timeout after extended processing
        $this->travel(35)->seconds(); // Exceed default timeout
        
        app(QueueTestService::class)->handleTestTimeout($jobId);
        
        $response = $this->get('/setup/queue-worker/status');
        $data = $response->json();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_TIMEOUT, $data['status']);
        $this->assertStringContainsString('timed out', $data['message']);
        $this->assertTrue($data['can_retry']);
        
        // Verify timeout troubleshooting guidance
        $this->assertArrayHasKey('troubleshooting', $data);
        $troubleshootingText = implode(' ', $data['troubleshooting']);
        $this->assertStringContainsString('queue worker may not be running', $troubleshootingText);
        $this->assertStringContainsString('php artisan queue:work', $troubleshootingText);

        // Step 4: Retry after timeout
        $response = $this->post('/setup/queue-worker/test');
        $newJobId = $response->json()['job_id'];
        
        // Step 5: Complete retry quickly to show recovery
        app(QueueTestService::class)->markTestJobCompleted($newJobId, 0.8);
        
        $response = $this->get('/setup/queue-worker/status');
        $data = $response->json();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $data['status']);
        $this->assertEquals(0.8, $data['processing_time']);
    }

    /**
     * Test job execution failure with detailed error reporting.
     * 
     * @covers Job execution failure handling
     */
    public function test_job_execution_failure_with_error_reporting()
    {
        Queue::fake();
        
        // Step 1: Start test
        $response = $this->post('/setup/queue-worker/test');
        $jobId = $response->json()['job_id'];
        
        // Step 2: Simulate job execution failure
        $errorMessage = 'Database connection lost during job execution';
        app(QueueTestService::class)->markTestJobFailed($jobId, $errorMessage);
        
        // Step 3: Check detailed error reporting
        $response = $this->get('/setup/queue-worker/status');
        $data = $response->json();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $data['status']);
        $this->assertEquals($errorMessage, $data['error_message']);
        $this->assertTrue($data['can_retry']);
        
        // Verify specific troubleshooting for job execution failures
        $this->assertArrayHasKey('troubleshooting', $data);
        $troubleshootingText = implode(' ', $data['troubleshooting']);
        $this->assertStringContainsString('Check Laravel logs', $troubleshootingText);
        $this->assertStringContainsString('database connection', $troubleshootingText);

        // Step 4: Test individual job status endpoint
        $response = $this->get("/setup/queue-worker/test/{$jobId}/status");
        $data = $response->json();
        
        $this->assertEquals('failed', $data['status']);
        $this->assertEquals($errorMessage, $data['error_message']);
        $this->assertArrayHasKey('failed_at', $data);
    }

    /**
     * Test network/AJAX failure scenarios and client-side recovery.
     * 
     * @covers Network failure handling
     */
    public function test_network_failure_scenarios()
    {
        // Step 1: Test malformed request handling
        $response = $this->post('/setup/queue-worker/test', [
            'invalid_data' => 'should be ignored'
        ]);
        
        $response->assertOk(); // Should handle gracefully
        
        // Step 2: Test missing CSRF token
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
                        ->post('/setup/queue-worker/test');
        
        $response->assertOk();
        
        // Step 3: Test invalid job ID lookup
        $response = $this->get('/setup/queue-worker/test/invalid-job-id/status');
        
        $response->assertOk();
        $data = $response->json();
        
        $this->assertEquals('not_found', $data['status']);
        $this->assertStringContainsString('not found', $data['message']);

        // Step 4: Test status endpoint with corrupted cache
        Cache::put(QueueWorkerStatus::CACHE_KEY, 'invalid-json-data', 3600);
        
        $response = $this->get('/setup/queue-worker/status');
        
        $response->assertOk();
        $data = $response->json();
        
        // Should fallback to not_tested status
        $this->assertEquals(QueueWorkerStatus::STATUS_NOT_TESTED, $data['status']);
    }

    /**
     * Test database connection failures and recovery.
     * 
     * @covers Database failure handling
     */
    public function test_database_connection_failures()
    {
        // Step 1: Simulate database connection failure during queue health check
        DB::shouldReceive('table')->andThrow(new \PDOException('Connection lost'));
        
        $queueTestService = app(QueueTestService::class);
        $result = $queueTestService->getQueueHealth();
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Error retrieving queue health', $result['message']);

        // Step 2: Test graceful degradation
        $response = $this->get('/setup/queue-worker/status');
        
        $response->assertOk(); // Should still respond even with DB issues
        
        // Step 3: Test recovery after DB connection restored
        DB::clearResolvedInstances();
        
        $response = $this->get('/setup/queue-worker/status');
        $response->assertOk();
    }

    /**
     * Test cache failure scenarios and fallback mechanisms.
     * 
     * @covers Cache failure handling
     */
    public function test_cache_failure_scenarios()
    {
        // Step 1: Simulate cache write failure
        Cache::shouldReceive('put')->andThrow(new \Exception('Cache write failed'));
        
        Queue::fake();
        
        $response = $this->post('/setup/queue-worker/test');
        
        // Should still work even if caching fails
        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['success']);

        // Step 2: Simulate cache read failure
        Cache::shouldReceive('get')->andThrow(new \Exception('Cache read failed'));
        
        $response = $this->get('/setup/queue-worker/status');
        
        $response->assertOk();
        $data = $response->json();
        
        // Should fallback to not_tested status
        $this->assertEquals(QueueWorkerStatus::STATUS_NOT_TESTED, $data['status']);
    }

    /**
     * Test concurrent failure scenarios and race condition handling.
     * 
     * @covers Concurrent failure handling
     */
    public function test_concurrent_failure_scenarios()
    {
        Queue::fake();
        
        // Step 1: Start multiple tests simultaneously
        $response1 = $this->post('/setup/queue-worker/test');
        $jobId1 = $response1->json()['job_id'];
        
        // Step 2: Try to start another test while first is running
        $response2 = $this->post('/setup/queue-worker/test');
        
        $this->assertFalse($response2->json()['success']);
        $this->assertStringContainsString('already in progress', $response2->json()['message']);

        // Step 3: Fail the first test
        app(QueueTestService::class)->markTestJobFailed($jobId1, 'First test failed');
        
        // Step 4: Should now be able to start a new test
        $response3 = $this->post('/setup/queue-worker/test');
        
        $this->assertTrue($response3->json()['success']);
        $this->assertNotEquals($jobId1, $response3->json()['job_id']);
    }

    /**
     * Test user guidance and troubleshooting message accuracy.
     * 
     * @covers User guidance accuracy
     */
    public function test_user_guidance_accuracy()
    {
        // Test different error types produce appropriate guidance
        $errorScenarios = [
            [
                'error' => 'Queue connection failed',
                'expected_guidance' => ['Check queue configuration', 'QUEUE_CONNECTION', '.env']
            ],
            [
                'error' => 'Job timeout',
                'expected_guidance' => ['queue worker may not be running', 'php artisan queue:work']
            ],
            [
                'error' => 'Database connection lost',
                'expected_guidance' => ['Check database connection', 'Laravel logs']
            ]
        ];

        foreach ($errorScenarios as $scenario) {
            Queue::fake();
            
            $response = $this->post('/setup/queue-worker/test');
            $jobId = $response->json()['job_id'];
            
            app(QueueTestService::class)->markTestJobFailed($jobId, $scenario['error']);
            
            $response = $this->get('/setup/queue-worker/status');
            $data = $response->json();
            
            $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $data['status']);
            $this->assertArrayHasKey('troubleshooting', $data);
            
            $troubleshootingText = implode(' ', $data['troubleshooting']);
            
            foreach ($scenario['expected_guidance'] as $expectedText) {
                $this->assertStringContainsString($expectedText, $troubleshootingText, 
                    "Expected guidance '{$expectedText}' not found for error: {$scenario['error']}");
            }
        }
    }

    /**
     * Test error logging and monitoring integration.
     * 
     * @covers Error logging
     */
    public function test_error_logging_and_monitoring()
    {
        Log::fake();
        
        // Test dispatch failure logging
        Queue::fake();
        Queue::shouldReceive('push')->andThrow(new \Exception('Test dispatch failure'));
        
        $response = $this->post('/setup/queue-worker/test');
        
        Log::assertLogged('error', function ($message, $context) {
            return str_contains($message, 'Failed to dispatch test queue job') &&
                   isset($context['error']) &&
                   str_contains($context['error'], 'Test dispatch failure');
        });

        // Test job failure logging
        Queue::fake();
        
        $response = $this->post('/setup/queue-worker/test');
        $jobId = $response->json()['job_id'];
        
        app(QueueTestService::class)->markTestJobFailed($jobId, 'Test job failure');
        
        Log::assertLogged('error', function ($message, $context) {
            return str_contains($message, 'Test queue job failed') &&
                   isset($context['job_id']) &&
                   isset($context['error_message']);
        });
    }

    /**
     * Test performance under error conditions.
     * 
     * @covers Error condition performance
     */
    public function test_performance_under_error_conditions()
    {
        // Test that error responses are still fast
        $startTime = microtime(true);
        
        for ($i = 0; $i < 10; $i++) {
            $this->get('/setup/queue-worker/test/invalid-job/status');
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Error responses should still be fast
        $this->assertLessThan(2.0, $totalTime);

        // Test that failed status caching works efficiently
        Queue::fake();
        Queue::shouldReceive('push')->andThrow(new \Exception('Consistent failure'));
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < 5; $i++) {
            $this->post('/setup/queue-worker/test');
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Should handle repeated failures efficiently
        $this->assertLessThan(3.0, $totalTime);
    }

    /**
     * Test complete error recovery documentation workflow.
     * 
     * @covers Error recovery documentation
     */
    public function test_complete_error_recovery_documentation()
    {
        // Step 1: Create comprehensive failure scenario
        Queue::fake();
        Queue::shouldReceive('push')->andThrow(new \Exception('Complete system failure'));
        
        $response = $this->post('/setup/queue-worker/test');
        
        // Step 2: Verify comprehensive error documentation
        $response = $this->get('/setup/queue-worker/status');
        $data = $response->json();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $data['status']);
        $this->assertArrayHasKey('troubleshooting', $data);
        $this->assertArrayHasKey('error_message', $data);
        $this->assertTrue($data['can_retry']);
        
        // Step 3: Verify troubleshooting covers all major areas
        $troubleshootingText = implode(' ', $data['troubleshooting']);
        
        $expectedTopics = [
            'queue configuration',
            'worker process',
            'Laravel logs',
            'manual verification'
        ];
        
        foreach ($expectedTopics as $topic) {
            $this->assertStringContainsString($topic, $troubleshootingText,
                "Troubleshooting should cover: {$topic}");
        }

        // Step 4: Test that setup instructions page shows error state
        $response = $this->get('/setup/instructions');
        
        $response->assertOk();
        $response->assertSee('queue_worker-status');
        $response->assertSee('Retry Test'); // Should show retry option
    }
}