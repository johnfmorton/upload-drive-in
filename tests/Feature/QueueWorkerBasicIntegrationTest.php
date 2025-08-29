<?php

namespace Tests\Feature;

use App\Services\QueueTestService;
use App\Services\SetupStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueWorkerBasicIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_setup_instructions_page_loads()
    {
        $response = $this->get('/setup/instructions');
        $response->assertStatus(200);
        $response->assertViewIs('setup.instructions');
    }

    public function test_general_status_refresh_excludes_queue_worker()
    {
        $response = $this->post('/setup/status/refresh');
        $response->assertStatus(200);
        
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        
        $statusData = $responseData['data']['statuses'];
        $this->assertIsArray($statusData);
        $this->assertArrayNotHasKey('queue_worker', $statusData);
    }

    public function test_queue_worker_status_endpoint_works()
    {
        $response = $this->get('/setup/queue-worker/status');
        $response->assertStatus(200);
        
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('queue_worker', $responseData['data']);
        
        $queueWorkerData = $responseData['data']['queue_worker'];
        $this->assertEquals('not_tested', $queueWorkerData['status']);
        $this->assertStringContainsString('Click the Test Queue Worker button below', $queueWorkerData['message']);
    }

    public function test_queue_worker_test_can_be_initiated()
    {
        Queue::fake();
        
        $response = $this->post('/setup/queue/test');
        $response->assertStatus(200);
        
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('test_job_id', $responseData);
        $this->assertArrayHasKey('queue_worker_status', $responseData);
        
        $queueWorkerStatus = $responseData['queue_worker_status'];
        $this->assertEquals('testing', $queueWorkerStatus['status']);
        $this->assertNotEmpty($queueWorkerStatus['message']);
    }

    public function test_queue_worker_test_status_can_be_polled()
    {
        Queue::fake();
        
        // First initiate a test
        $response = $this->post('/setup/queue/test');
        $response->assertStatus(200);
        
        $testJobId = $response->json()['test_job_id'];
        
        // Then poll for status
        $response = $this->get("/setup/queue/test/status?test_job_id={$testJobId}");
        $response->assertStatus(200);
        
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('queue_worker_status', $responseData);
        
        $queueWorkerStatus = $responseData['queue_worker_status'];
        $this->assertEquals('testing', $queueWorkerStatus['status']);
    }

    public function test_queue_worker_status_persists_in_cache()
    {
        // Set up a cached status
        Cache::put('setup_queue_worker_status', [
            'status' => 'completed',
            'message' => 'Queue worker is functioning properly! (1.23s)',
            'test_completed_at' => now()->subMinutes(10)->toISOString(),
            'processing_time' => 1.23,
            'test_job_id' => 'test-123',
            'can_retry' => true
        ], 3600);

        $response = $this->get('/setup/queue-worker/status');
        $response->assertStatus(200);
        
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        
        $queueWorkerData = $responseData['data']['queue_worker'];
        $this->assertEquals('completed', $queueWorkerData['status']);
        $this->assertStringContainsString('functioning properly', $queueWorkerData['message']);
    }

    public function test_expired_cache_returns_not_tested_status()
    {
        // Set up an expired cached status (older than 1 hour)
        Cache::put('setup_queue_worker_status', [
            'status' => 'completed',
            'message' => 'Queue worker is functioning properly! (1.23s)',
            'test_completed_at' => now()->subHours(2)->toISOString(), // Expired
            'processing_time' => 1.23,
            'test_job_id' => 'old-test-123',
            'can_retry' => true
        ], 3600);

        $setupStatusService = app(SetupStatusService::class);
        $queueStatus = $setupStatusService->getQueueWorkerStatus();
        
        $this->assertEquals('not_tested', $queueStatus['status']);
        $this->assertStringContainsString('Click the Test Queue Worker button below', $queueStatus['message']);
    }

    public function test_csrf_protection_is_enforced()
    {
        // Test without CSRF token should fail
        $response = $this->post('/setup/queue/test', [], [
            'X-CSRF-TOKEN' => 'invalid-token'
        ]);
        
        // Should return 419 (CSRF token mismatch) or redirect
        $this->assertContains($response->status(), [419, 302]);
    }

    public function test_backward_compatibility_with_existing_endpoints()
    {
        // Test that existing setup instructions functionality still works
        $response = $this->get('/setup/instructions');
        $response->assertStatus(200);

        // Test that status refresh still works
        $response = $this->post('/setup/status/refresh');
        $response->assertStatus(200);
        
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('statuses', $responseData['data']);
    }
}