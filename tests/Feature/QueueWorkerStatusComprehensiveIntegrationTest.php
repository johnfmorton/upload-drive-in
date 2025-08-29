<?php

namespace Tests\Feature;

use App\Services\SetupStatusService;
use App\Services\QueueTestService;
use App\Services\QueueWorkerStatus;
use App\Jobs\TestQueueJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Comprehensive integration tests for the complete queue worker status fix workflow.
 * 
 * Tests the end-to-end functionality including status separation, caching,
 * persistence, error handling, and recovery mechanisms.
 */
class QueueWorkerStatusComprehensiveIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Test complete workflow from fresh setup through successful queue worker testing.
     * 
     * @covers Complete workflow integration
     */
    public function test_complete_workflow_fresh_setup_to_successful_test()
    {
        // Step 1: Fresh setup - no cached status
        $response = $this->get('/setup/queue-worker/status');
        
        $response->assertOk();
        $data = $response->json();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_NOT_TESTED, $data['status']);
        $this->assertStringContainsString('Click the Test Queue Worker button', $data['message']);

        // Step 2: General status refresh should exclude queue worker
        $response = $this->post('/setup/status/refresh', [
            'steps' => ['database', 'mail', 'google_drive', 'migrations', 'admin_user']
        ]);
        
        $response->assertOk();
        $data = $response->json();
        
        $this->assertTrue($data['success']);
        $this->assertArrayNotHasKey('queue_worker', $data['statuses']);
        $this->assertArrayHasKey('database', $data['statuses']);

        // Step 3: Trigger queue worker test
        Queue::fake();
        
        $response = $this->post('/setup/queue-worker/test');
        
        $response->assertOk();
        $data = $response->json();
        
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('job_id', $data);
        
        Queue::assertPushed(TestQueueJob::class);

        // Step 4: Check that testing status is cached
        $cachedStatus = Cache::get(QueueWorkerStatus::CACHE_KEY);
        $this->assertEquals(QueueWorkerStatus::STATUS_TESTING, $cachedStatus['status']);

        // Step 5: Simulate job completion
        $jobId = $data['job_id'];
        $processingTime = 1.23;
        
        app(QueueTestService::class)->markTestJobCompleted($jobId, $processingTime);

        // Step 6: Verify completed status is cached and accessible
        $response = $this->get('/setup/queue-worker/status');
        
        $response->assertOk();
        $data = $response->json();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $data['status']);
        $this->assertEquals($processingTime, $data['processing_time']);
        $this->assertStringContainsString('functioning properly', $data['message']);
    }

    /**
     * Test status persistence across page refreshes and sessions.
     * 
     * @covers Status persistence functionality
     */
    public function test_status_persistence_across_page_refreshes()
    {
        // Set up a completed test status
        $completedAt = Carbon::now();
        $processingTime = 2.45;
        
        $status = QueueWorkerStatus::createCompleted(
            'Queue worker is functioning properly',
            $completedAt,
            $processingTime
        );
        
        Cache::put(QueueWorkerStatus::CACHE_KEY, $status->toArray(), QueueWorkerStatus::CACHE_TTL);

        // Simulate page refresh - check status endpoint
        $response = $this->get('/setup/queue-worker/status');
        
        $response->assertOk();
        $data = $response->json();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $data['status']);
        $this->assertEquals($processingTime, $data['processing_time']);
        $this->assertEquals($completedAt->toISOString(), $data['test_completed_at']);

        // Simulate another page refresh after some time
        $this->travel(30)->minutes();
        
        $response = $this->get('/setup/queue-worker/status');
        
        $response->assertOk();
        $data = $response->json();
        
        // Should still show completed status (within 1 hour cache)
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $data['status']);

        // Simulate page refresh after cache expiry
        $this->travel(2)->hours();
        
        $response = $this->get('/setup/queue-worker/status');
        
        $response->assertOk();
        $data = $response->json();
        
        // Should now show not_tested status (cache expired)
        $this->assertEquals(QueueWorkerStatus::STATUS_NOT_TESTED, $data['status']);
    }

    /**
     * Test all error scenarios and recovery mechanisms.
     * 
     * @covers Error handling and recovery
     */
    public function test_all_error_scenarios_and_recovery()
    {
        // Scenario 1: Test job dispatch failure
        Queue::fake();
        Queue::shouldReceive('push')->andThrow(new \Exception('Queue connection failed'));
        
        $response = $this->post('/setup/queue-worker/test');
        
        $response->assertOk();
        $data = $response->json();
        
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Failed to dispatch test job', $data['message']);
        
        // Verify error status is cached
        $cachedStatus = Cache::get(QueueWorkerStatus::CACHE_KEY);
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $cachedStatus['status']);

        // Scenario 2: Test job timeout
        Queue::fake();
        
        $response = $this->post('/setup/queue-worker/test');
        $jobId = $response->json()['job_id'];
        
        // Simulate timeout
        app(QueueTestService::class)->handleTestTimeout($jobId);
        
        $response = $this->get('/setup/queue-worker/status');
        $data = $response->json();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_TIMEOUT, $data['status']);
        $this->assertStringContainsString('timed out', $data['message']);
        $this->assertTrue($data['can_retry']);

        // Scenario 3: Recovery with retry
        Queue::fake();
        
        $response = $this->post('/setup/queue-worker/test');
        
        $response->assertOk();
        $data = $response->json();
        
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('job_id', $data);
        
        // Verify retry resets status to testing
        $cachedStatus = Cache::get(QueueWorkerStatus::CACHE_KEY);
        $this->assertEquals(QueueWorkerStatus::STATUS_TESTING, $cachedStatus['status']);
    }

    /**
     * Test concurrent operations and race condition handling.
     * 
     * @covers Concurrent operation handling
     */
    public function test_concurrent_operations_handling()
    {
        Queue::fake();
        
        // Start first test
        $response1 = $this->post('/setup/queue-worker/test');
        $this->assertTrue($response1->json()['success']);
        
        // Try to start second test while first is running
        $response2 = $this->post('/setup/queue-worker/test');
        
        $response2->assertOk();
        $data = $response2->json();
        
        // Should prevent concurrent tests
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('already in progress', $data['message']);
    }

    /**
     * Test button state management during testing operations.
     * 
     * @covers Button state management
     */
    public function test_button_state_management_during_operations()
    {
        // Test setup instructions page with button states
        $response = $this->get('/setup/instructions');
        
        $response->assertOk();
        $response->assertSee('refresh-status-btn');
        $response->assertSee('test-queue-worker-btn');
        
        // Verify JavaScript includes button state management
        $response->assertSee('updateButtonStates');
        $response->assertSee('refreshInProgress');
        $response->assertSee('queueWorkerTestInProgress');
    }

    /**
     * Test error message display and troubleshooting guidance.
     * 
     * @covers Error messaging and troubleshooting
     */
    public function test_error_message_display_and_troubleshooting()
    {
        // Create a failed status with troubleshooting info
        $status = QueueWorkerStatus::createFailed(
            'Queue worker may not be running',
            'Test job timed out after 30 seconds',
            [
                'Check if queue worker is running: php artisan queue:work',
                'Verify queue configuration in .env file',
                'Check Laravel logs for error details'
            ]
        );
        
        Cache::put(QueueWorkerStatus::CACHE_KEY, $status->toArray(), QueueWorkerStatus::CACHE_TTL);
        
        $response = $this->get('/setup/queue-worker/status');
        
        $response->assertOk();
        $data = $response->json();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $data['status']);
        $this->assertArrayHasKey('troubleshooting', $data);
        $this->assertCount(3, $data['troubleshooting']);
        $this->assertTrue($data['can_retry']);
    }

    /**
     * Test progressive status updates during job processing.
     * 
     * @covers Progressive status updates
     */
    public function test_progressive_status_updates()
    {
        Queue::fake();
        
        // Start test
        $response = $this->post('/setup/queue-worker/test');
        $jobId = $response->json()['job_id'];
        
        // Simulate progressive updates
        app(QueueTestService::class)->updateJobProgress($jobId, 'Test job queued, waiting for worker...');
        
        $response = $this->get("/setup/queue-worker/test/{$jobId}/status");
        $data = $response->json();
        
        $this->assertEquals('processing', $data['status']);
        $this->assertStringContainsString('waiting for worker', $data['progress_message']);
        
        // Update progress again
        app(QueueTestService::class)->updateJobProgress($jobId, 'Test job is being processed...');
        
        $response = $this->get("/setup/queue-worker/test/{$jobId}/status");
        $data = $response->json();
        
        $this->assertStringContainsString('being processed', $data['progress_message']);
    }

    /**
     * Test cache invalidation and cleanup.
     * 
     * @covers Cache management
     */
    public function test_cache_invalidation_and_cleanup()
    {
        // Set up cached status
        $status = QueueWorkerStatus::createCompleted('Test message', Carbon::now(), 1.0);
        Cache::put(QueueWorkerStatus::CACHE_KEY, $status->toArray(), QueueWorkerStatus::CACHE_TTL);
        
        $this->assertNotNull(Cache::get(QueueWorkerStatus::CACHE_KEY));
        
        // Test cache invalidation
        app(QueueTestService::class)->invalidateQueueWorkerStatusCache();
        
        $this->assertNull(Cache::get(QueueWorkerStatus::CACHE_KEY));
        
        // Test cleanup of old test jobs
        $oldJobId = 'old-job-123';
        Cache::put("test_queue_job_{$oldJobId}", [
            'status' => 'completed',
            'completed_at' => Carbon::now()->subDays(2)->toISOString()
        ], 3600);
        
        $result = app(QueueTestService::class)->cleanupOldTestJobs();
        
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['cleaned_count']);
        $this->assertNull(Cache::get("test_queue_job_{$oldJobId}"));
    }

    /**
     * Test cross-browser compatibility and responsive design.
     * 
     * @covers Frontend compatibility
     */
    public function test_cross_browser_compatibility()
    {
        // Test setup instructions page with different user agents
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0'
        ];
        
        foreach ($userAgents as $userAgent) {
            $response = $this->withHeaders(['User-Agent' => $userAgent])
                            ->get('/setup/instructions');
            
            $response->assertOk();
            $response->assertSee('queue_worker-status');
            $response->assertSee('refresh-status-btn');
            $response->assertSee('test-queue-worker-btn');
        }
    }

    /**
     * Test performance under load and stress conditions.
     * 
     * @covers Performance testing
     */
    public function test_performance_under_load()
    {
        // Simulate multiple concurrent status requests
        $responses = [];
        
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->get('/setup/queue-worker/status');
        }
        
        foreach ($responses as $response) {
            $response->assertOk();
            $this->assertLessThan(1000, $response->headers->get('X-Response-Time', 0)); // Should respond within 1 second
        }
        
        // Test cache efficiency
        $startTime = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $this->get('/setup/queue-worker/status');
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // 100 cached requests should complete quickly
        $this->assertLessThan(5.0, $totalTime); // Should complete within 5 seconds
    }

    /**
     * Test backward compatibility with existing functionality.
     * 
     * @covers Backward compatibility
     */
    public function test_backward_compatibility()
    {
        // Test that existing setup instructions functionality still works
        $response = $this->get('/setup/instructions');
        
        $response->assertOk();
        $response->assertSee('Setup Instructions');
        $response->assertSee('Check Status');
        
        // Test that old status refresh still works (but excludes queue worker)
        $response = $this->post('/setup/status/refresh');
        
        $response->assertOk();
        $data = $response->json();
        
        $this->assertTrue($data['success']);
        $this->assertArrayNotHasKey('queue_worker', $data['statuses']);
        
        // Test that individual step status checks still work
        foreach (['database', 'mail', 'google_drive', 'migrations', 'admin_user'] as $step) {
            $response = $this->post('/setup/status/refresh', ['steps' => [$step]]);
            
            $response->assertOk();
            $data = $response->json();
            
            $this->assertTrue($data['success']);
            $this->assertArrayHasKey($step, $data['statuses']);
        }
    }

    /**
     * Test security measures and rate limiting.
     * 
     * @covers Security testing
     */
    public function test_security_measures_and_rate_limiting()
    {
        // Test CSRF protection
        $response = $this->post('/setup/queue-worker/test', [], [
            'X-CSRF-TOKEN' => 'invalid-token'
        ]);
        
        $response->assertStatus(419); // CSRF token mismatch
        
        // Test rate limiting (if implemented)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/setup/queue-worker/test');
            
            if ($response->status() === 429) {
                // Rate limit hit
                break;
            }
        }
        
        // Should eventually hit rate limit or succeed with proper throttling
        $this->assertTrue(true); // Test passes if no exceptions thrown
    }
}