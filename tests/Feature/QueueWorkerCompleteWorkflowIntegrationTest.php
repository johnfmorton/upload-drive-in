<?php

namespace Tests\Feature;

use App\Services\QueueTestService;
use App\Services\SetupStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueWorkerCompleteWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private SetupStatusService $setupStatusService;
    private QueueTestService $queueTestService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupStatusService = app(SetupStatusService::class);
        $this->queueTestService = app(QueueTestService::class);
        
        // Clear any cached status
        Cache::flush();
    }

    /** @test */
    public function test_complete_workflow_from_fresh_setup_to_successful_queue_test()
    {
        // 1. Fresh setup - verify initial state
        $response = $this->get('/setup/instructions');
        $response->assertStatus(200);
        
        // Check that queue worker shows initial message
        $queueStatus = $this->setupStatusService->getQueueWorkerStatus();
        $this->assertEquals('not_tested', $queueStatus['status']);
        $this->assertStringContainsString('Click the Test Queue Worker button below', $queueStatus['message']);

        // 2. Test general status refresh excludes queue worker
        $response = $this->post('/setup/status/refresh');
        $response->assertStatus(200);
        
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $statusData = $responseData['data']['statuses'];
        $this->assertArrayNotHasKey('queue_worker', $statusData);

        // 3. Test standalone queue worker test
        Queue::fake();
        
        $response = $this->post('/setup/queue/test');
        $response->assertStatus(200);
        
        $testData = $response->json();
        $this->assertTrue($testData['success']);
        $this->assertEquals('testing', $testData['queue_worker_status']['status']);
        $this->assertStringContainsString('Test job queued', $testData['queue_worker_status']['message']);

        // 4. Simulate successful job completion
        $testJobId = $testData['test_job_id'];
        Cache::put("test_queue_job_result_{$testJobId}", [
            'status' => 'completed',
            'completed_at' => now()->toISOString(),
            'processing_time' => 1.23,
            'message' => 'Test job completed successfully'
        ], 3600);

        // 5. Poll for results
        $response = $this->get("/setup/queue/test/status?test_job_id={$testJobId}");
        $response->assertStatus(200);
        
        $pollData = $response->json();
        $this->assertTrue($pollData['success']);
        $this->assertEquals('completed', $pollData['queue_worker_status']['status']);
        $this->assertStringContainsString('functioning properly', $pollData['queue_worker_status']['message']);
        $this->assertEquals(1.23, $pollData['queue_worker_status']['processing_time']);

        // 6. Verify status persistence
        $cachedStatus = $this->setupStatusService->getQueueWorkerStatus();
        $this->assertEquals('completed', $cachedStatus['status']);
        $this->assertStringContainsString('functioning properly', $cachedStatus['message']);
    }

    /** @test */
    public function test_status_persistence_across_page_refreshes()
    {
        // Set up successful test result in cache
        $testJobId = 'test-job-123';
        Cache::put('setup_queue_worker_status', [
            'status' => 'completed',
            'message' => 'Queue worker is functioning properly! (1.45s)',
            'test_completed_at' => now()->subMinutes(10)->toISOString(),
            'processing_time' => 1.45,
            'test_job_id' => $testJobId
        ], 3600);

        // First page load
        $response = $this->get('/setup/instructions');
        $response->assertStatus(200);
        
        $queueStatus = $this->setupStatusService->getQueueWorkerStatus();
        $this->assertEquals('completed', $queueStatus['status']);
        $this->assertStringContainsString('functioning properly', $queueStatus['message']);

        // Simulate page refresh by making another request
        $response = $this->get('/setup/instructions');
        $response->assertStatus(200);
        
        // Status should still be persisted
        $queueStatus = $this->setupStatusService->getQueueWorkerStatus();
        $this->assertEquals('completed', $queueStatus['status']);
        $this->assertStringContainsString('functioning properly', $queueStatus['message']);

        // Test queue worker status endpoint
        $response = $this->get('/setup/queue-worker/status');
        $response->assertStatus(200);
        
        $statusData = $response->json();
        $this->assertTrue($statusData['success']);
        $this->assertEquals('completed', $statusData['data']['queue_worker']['status']);
    }

    /** @test */
    public function test_expired_cache_handling()
    {
        // Set up expired test result
        Cache::put('setup_queue_worker_status', [
            'status' => 'completed',
            'message' => 'Queue worker is functioning properly! (1.45s)',
            'test_completed_at' => now()->subHours(2)->toISOString(), // Expired
            'processing_time' => 1.45,
            'test_job_id' => 'old-test-123'
        ], 3600);

        $queueStatus = $this->setupStatusService->getQueueWorkerStatus();
        $this->assertEquals('not_tested', $queueStatus['status']);
        $this->assertStringContainsString('Click the Test Queue Worker button below', $queueStatus['message']);
    }

    /** @test */
    public function test_all_error_scenarios_and_recovery()
    {
        Queue::fake();

        // Test 1: Job timeout scenario
        $response = $this->post('/setup/queue/test');
        $response->assertStatus(200);
        
        $testJobId = $response->json()['test_job_id'];

        // Don't set any result - simulate timeout
        sleep(1); // Brief pause to simulate time passing
        
        $response = $this->get("/setup/queue/test/status?test_job_id={$testJobId}");
        $response->assertStatus(200);
        
        $pollData = $response->json();
        $this->assertTrue($pollData['success']);
        $this->assertEquals('testing', $pollData['queue_worker_status']['status']);
        $this->assertStringContainsString('Test job queued', $pollData['queue_worker_status']['message']);

        // Test 2: Job failure
        Cache::put("test_queue_job_result_{$testJobId}", [
            'status' => 'failed',
            'completed_at' => now()->toISOString(),
            'error_message' => 'Job processing failed',
            'message' => 'Test job failed: Job processing failed'
        ], 3600);

        $response = $this->get("/setup/queue/test/status?test_job_id={$testJobId}");
        $response->assertStatus(200);
        
        $pollData = $response->json();
        $this->assertTrue($pollData['success']);
        $this->assertEquals('failed', $pollData['queue_worker_status']['status']);
        $this->assertStringContainsString('Test job failed', $pollData['queue_worker_status']['message']);
        $this->assertTrue($pollData['queue_worker_status']['can_retry']);

        // Test 3: Recovery via retry
        $response = $this->post('/setup/queue/test');
        $response->assertStatus(200);
        
        $newTestJobId = $response->json()['test_job_id'];
        $this->assertNotEquals($testJobId, $newTestJobId);
    }

    /** @test */
    public function test_backward_compatibility_with_existing_functionality()
    {
        // Test that existing setup instructions page still works
        $response = $this->get('/setup/instructions');
        $response->assertStatus(200);
        $response->assertViewIs('setup.instructions');

        // Test that all existing status endpoints still work
        $response = $this->post('/setup/status/refresh');
        $response->assertStatus(200);
        
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $statusData = $responseData['data']['statuses'];
        $this->assertIsArray($statusData);
        
        // Verify queue_worker is excluded from general status
        $this->assertArrayNotHasKey('queue_worker', $statusData);

        // Test queue worker status endpoint works separately
        $response = $this->get('/setup/queue-worker/status');
        $response->assertStatus(200);
        
        $queueData = $response->json();
        $this->assertTrue($queueData['success']);
        $this->assertArrayHasKey('queue_worker', $queueData['data']);
    }

    /** @test */
    public function test_concurrent_status_refresh_and_queue_test()
    {
        Queue::fake();

        // Test general status refresh
        $response = $this->post('/setup/status/refresh');
        $response->assertStatus(200);
        
        $statusData = $response->json();
        $this->assertTrue($statusData['success']);
        
        // Test queue worker test separately
        $response = $this->post('/setup/queue/test');
        $response->assertStatus(200);
        
        $queueData = $response->json();
        $this->assertTrue($queueData['success']);
        $this->assertEquals('testing', $queueData['queue_worker_status']['status']);
        $this->assertArrayHasKey('test_job_id', $queueData);
    }

    /** @test */
    public function test_rate_limiting_and_security_measures()
    {
        Queue::fake();

        // Test rate limiting - make multiple rapid requests
        for ($i = 0; $i < 3; $i++) {
            $response = $this->post('/setup/queue/test');
            $response->assertStatus(200);
        }

        // Test CSRF protection
        $response = $this->post('/setup/queue/test', [], [
            'X-CSRF-TOKEN' => 'invalid-token'
        ]);
        $response->assertStatus(419); // CSRF token mismatch
    }

    /** @test */
    public function test_cache_invalidation_and_cleanup()
    {
        // Set up test data in cache
        $testJobId = 'test-job-456';
        Cache::put('setup_queue_worker_status', [
            'status' => 'completed',
            'test_job_id' => $testJobId
        ], 3600);
        
        Cache::put("test_queue_job_result_{$testJobId}", [
            'status' => 'completed'
        ], 3600);

        // Verify cache exists
        $this->assertTrue(Cache::has('setup_queue_worker_status'));
        $this->assertTrue(Cache::has("test_queue_job_result_{$testJobId}"));

        // Trigger new test which should invalidate old cache
        Queue::fake();
        $response = $this->post('/setup/queue/test');
        $response->assertStatus(200);

        // Old cache should be cleared
        $cachedStatus = Cache::get('setup_queue_worker_status');
        $this->assertEquals('testing', $cachedStatus['status']);
        
        // Old job result should be cleaned up
        $this->assertFalse(Cache::has("test_queue_job_result_{$testJobId}"));
    }
}