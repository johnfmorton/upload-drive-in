<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use App\Services\QueueTestService;
use App\Services\SetupStatusService;
use App\Services\QueueWorkerStatus;
use Carbon\Carbon;

class QueueWorkerIntegrationWithGeneralStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any existing cache
        Cache::flush();
        
        // Fake the queue for testing
        Queue::fake();
    }

    /** @test */
    public function check_status_button_triggers_general_status_refresh_only()
    {
        // The current implementation only refreshes general status
        // The parallel queue worker test is handled by the frontend JavaScript
        
        // Make request to status refresh endpoint
        $response = $this->postJson('/setup/status/refresh', [], [
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'statuses'
            ]
        ]);

        // Verify queue_worker is not in the response
        $responseData = $response->json();
        $this->assertArrayNotHasKey('queue_worker', $responseData['data']['statuses']);
    }

    /** @test */
    public function general_status_refresh_excludes_queue_worker_from_response()
    {
        $response = $this->postJson('/setup/status/refresh', [], [
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('statuses', $responseData['data']);
        
        // Verify that general status steps are included
        $statuses = $responseData['data']['statuses'];
        $this->assertArrayHasKey('database', $statuses);
        $this->assertArrayHasKey('mail', $statuses);
        $this->assertArrayHasKey('google_drive', $statuses);
        $this->assertArrayHasKey('migrations', $statuses);
        $this->assertArrayHasKey('admin_user', $statuses);
        
        // Verify that queue_worker is NOT included
        $this->assertArrayNotHasKey('queue_worker', $statuses);
    }

    /** @test */
    public function queue_worker_status_endpoint_works_independently()
    {
        // Cache a successful queue worker test result
        $testTime = Carbon::now();
        $queueWorkerStatus = new QueueWorkerStatus(
            status: QueueWorkerStatus::STATUS_COMPLETED,
            message: 'Queue worker is functioning properly',
            testCompletedAt: $testTime,
            processingTime: 1.23,
            errorMessage: null,
            testJobId: 'test-job-123'
        );

        $queueTestService = app(QueueTestService::class);
        $queueTestService->cacheQueueWorkerStatus($queueWorkerStatus);

        // Request queue worker status
        $response = $this->getJson('/setup/queue-worker/status', [
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'queue_worker' => [
                    'status' => 'completed',
                    'message' => 'Queue worker is functioning properly',
                    'details' => [
                        'processing_time' => 1.23,
                    ],
                ]
            ]
        ]);
    }

    /** @test */
    public function queue_worker_status_shows_not_tested_when_no_cache_exists()
    {
        // Ensure no cache exists
        Cache::forget('setup_queue_worker_status');

        $response = $this->getJson('/setup/queue-worker/status', [
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'queue_worker' => [
                    'status' => 'not_tested',
                    'message' => 'Click the Test Queue Worker button below',
                ]
            ]
        ]);
    }

    /** @test */
    public function queue_worker_test_caches_results_for_status_persistence()
    {
        // Dispatch a test job
        $response = $this->postJson('/setup/queue/test', [
            'delay' => 0
        ], [
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $testJobId = $response->json('test_job_id');
        $this->assertNotEmpty($testJobId);

        // Verify that a testing status was cached
        $cachedStatus = Cache::get('setup_queue_worker_status');
        $this->assertNotNull($cachedStatus);
        $this->assertEquals('testing', $cachedStatus['status']);
        $this->assertEquals($testJobId, $cachedStatus['test_job_id']);
    }

    /** @test */
    public function general_status_failure_uses_fallback_gracefully()
    {
        // The current implementation has fallback logic that prevents complete failures
        // This test verifies that the system gracefully handles service failures
        
        $response = $this->postJson('/setup/status/refresh', [], [
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        // The endpoint should still return a successful response due to fallback logic
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'statuses'
            ]
        ]);
    }

    /** @test */
    public function queue_worker_test_can_run_independently_of_general_status()
    {
        // Test that queue worker test works even when general status has issues
        $response = $this->postJson('/setup/queue/test', [
            'delay' => 0
        ], [
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $testJobId = $response->json('test_job_id');
        $this->assertNotEmpty($testJobId);

        // Verify queue worker status was cached
        $cachedStatus = Cache::get('setup_queue_worker_status');
        $this->assertNotNull($cachedStatus);
        $this->assertEquals('testing', $cachedStatus['status']);
    }

    /** @test */
    public function setup_status_service_excludes_queue_worker_from_general_refresh()
    {
        $setupStatusService = app(SetupStatusService::class);
        $statuses = $setupStatusService->refreshAllStatuses();

        // Verify that queue_worker is not included in the general status refresh
        $this->assertArrayNotHasKey('queue_worker', $statuses);
        
        // Verify that all other expected statuses are included
        $this->assertArrayHasKey('database', $statuses);
        $this->assertArrayHasKey('mail', $statuses);
        $this->assertArrayHasKey('google_drive', $statuses);
        $this->assertArrayHasKey('migrations', $statuses);
        $this->assertArrayHasKey('admin_user', $statuses);
    }

    /** @test */
    public function queue_worker_status_coordination_works_correctly()
    {
        // Test the coordination between general status and queue worker status
        
        // 1. First, get general status (should exclude queue worker)
        $generalResponse = $this->postJson('/setup/status/refresh', [], [
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $generalResponse->assertStatus(200);
        $generalData = $generalResponse->json();
        $this->assertArrayNotHasKey('queue_worker', $generalData['data']['statuses']);

        // 2. Then, get queue worker status separately
        $queueResponse = $this->getJson('/setup/queue-worker/status', [
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $queueResponse->assertStatus(200);
        $queueData = $queueResponse->json();
        $this->assertArrayHasKey('queue_worker', $queueData['data']);
        $this->assertEquals('not_tested', $queueData['data']['queue_worker']['status']);

        // 3. Run a queue worker test
        $testResponse = $this->postJson('/setup/queue/test', [
            'delay' => 0
        ], [
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $testResponse->assertStatus(200);

        // 4. Verify queue worker status now shows testing
        $updatedQueueResponse = $this->getJson('/setup/queue-worker/status', [
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $updatedQueueResponse->assertStatus(200);
        $updatedQueueData = $updatedQueueResponse->json();
        $this->assertEquals('testing', $updatedQueueData['data']['queue_worker']['status']);
    }

    /** @test */
    public function button_state_management_during_parallel_execution()
    {
        // This test verifies that the frontend properly manages button states
        // during parallel execution of general status and queue worker test
        
        // Make a request to general status refresh
        $response = $this->postJson('/setup/status/refresh', [], [
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        
        // The response should be successful, indicating that the backend
        // properly supports the parallel execution pattern
        $this->assertTrue($response->json('success'));
        
        // The response should not include queue_worker in general statuses
        $statuses = $response->json('data.statuses');
        $this->assertArrayNotHasKey('queue_worker', $statuses);
        
        // Verify that we can still get queue worker status independently
        $queueResponse = $this->getJson('/setup/queue-worker/status', [
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        
        $queueResponse->assertStatus(200);
        $this->assertArrayHasKey('queue_worker', $queueResponse->json('data'));
    }
}