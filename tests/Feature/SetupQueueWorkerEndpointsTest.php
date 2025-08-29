<?php

namespace Tests\Feature;

use App\Services\QueueTestService;
use App\Services\QueueWorkerStatus;
use App\Services\SetupStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SetupQueueWorkerEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable setup for these tests
        config(['app.setup_enabled' => true]);
        
        // Clear any existing cache
        Cache::flush();
    }

    /** @test */
    public function setup_status_refresh_excludes_queue_worker_from_response()
    {
        $response = $this->postJson('/setup/status/refresh');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'statuses',
                    'summary',
                    'refreshed_at',
                    'request_id',
                    'duration_ms'
                ],
                'message'
            ]);

        // Verify queue_worker is not included in the statuses
        $statuses = $response->json('data.statuses');
        $this->assertArrayNotHasKey('queue_worker', $statuses);
        
        // Verify other expected statuses are present
        $expectedStatuses = ['database', 'mail', 'google_drive', 'migrations', 'admin_user'];
        foreach ($expectedStatuses as $status) {
            $this->assertArrayHasKey($status, $statuses);
        }
    }

    /** @test */
    public function queue_worker_status_endpoint_returns_not_tested_by_default()
    {
        $response = $this->getJson('/setup/queue-worker/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'queue_worker' => [
                        'status' => 'not_tested',
                        'message' => 'Click the Test Queue Worker button below'
                    ]
                ]
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'queue_worker' => [
                        'status',
                        'message',
                        'details',
                        'checked_at',
                        'step_name',
                        'priority',
                        'can_retry'
                    ],
                    'retrieved_at',
                    'request_id',
                    'duration_ms'
                ],
                'message'
            ]);
    }

    /** @test */
    public function queue_worker_status_endpoint_returns_cached_completed_status()
    {
        // Cache a completed status
        $completedStatus = QueueWorkerStatus::completed(1.234, 'test_job_123');
        Cache::put(QueueWorkerStatus::CACHE_KEY, $completedStatus->toArray(), 3600);

        $response = $this->getJson('/setup/queue-worker/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'queue_worker' => [
                        'status' => 'completed',
                        'details' => [
                            'processing_time' => 1.234,
                            'test_job_id' => 'test_job_123'
                        ]
                    ]
                ]
            ]);
    }

    /** @test */
    public function queue_worker_status_endpoint_handles_expired_cache()
    {
        // Clear cache to ensure we start fresh
        Cache::forget(QueueWorkerStatus::CACHE_KEY);

        // The endpoint should return not_tested when no cache exists
        $response = $this->getJson('/setup/queue-worker/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'queue_worker' => [
                        'status' => 'not_tested',
                        'message' => 'Click the Test Queue Worker button below'
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_queue_endpoint_caches_testing_status()
    {
        $response = $this->postJson('/setup/queue/test', [
            'delay' => 0
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'success',
                'test_job_id',
                'queue_worker_status' => [
                    'status',
                    'message',
                    'test_job_id'
                ],
                'message'
            ]);

        $testJobId = $response->json('test_job_id');
        $this->assertNotEmpty($testJobId);

        // Verify the testing status is cached
        $cachedStatus = Cache::get(QueueWorkerStatus::CACHE_KEY);
        $this->assertNotNull($cachedStatus);
        $this->assertEquals('testing', $cachedStatus['status']);
        $this->assertEquals($testJobId, $cachedStatus['test_job_id']);
    }

    /** @test */
    public function check_queue_test_status_endpoint_returns_job_and_worker_status()
    {
        // First dispatch a test job
        $queueTestService = app(QueueTestService::class);
        $testJobId = $queueTestService->dispatchTestJob();

        $response = $this->getJson('/setup/queue/test/status?test_job_id=' . $testJobId);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'success',
                'status' => [
                    'test_job_id',
                    'status'
                ],
                'queue_worker_status' => [
                    'status'
                ]
            ]);

        // Verify the test job ID is returned
        $this->assertEquals($testJobId, $response->json('status.test_job_id'));
    }

    /** @test */
    public function check_queue_test_status_requires_test_job_id()
    {
        $response = $this->getJson('/setup/queue/test/status');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'message' => 'Test job ID is required.',
                    'code' => 'MISSING_JOB_ID'
                ]
            ]);
    }

    /** @test */
    public function queue_worker_status_endpoint_handles_security_validation()
    {
        // Mock security service to block request
        $this->mock(\App\Services\SetupSecurityService::class, function ($mock) {
            $mock->shouldReceive('shouldBlockRequest')->andReturn(true);
        });

        $response = $this->getJson('/setup/queue-worker/status');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'message' => 'Request blocked for security reasons.',
                    'code' => 'SECURITY_BLOCK'
                ]
            ]);
    }

    /** @test */
    public function setup_status_refresh_handles_service_errors_gracefully()
    {
        // Mock SetupStatusService to throw an exception
        $this->mock(SetupStatusService::class, function ($mock) {
            $mock->shouldReceive('refreshAllStatuses')
                ->andThrow(new \Exception('Service temporarily unavailable'));
            $mock->shouldReceive('getDetailedStepStatuses')
                ->andThrow(new \Exception('Fallback also failed'));
            $mock->shouldReceive('getStatusSummary')
                ->andThrow(new \Exception('Summary failed'));
        });

        $response = $this->postJson('/setup/status/refresh');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'REFRESH_FAILED'
                ]
            ]);
    }

    /** @test */
    public function queue_worker_status_endpoint_handles_service_errors_gracefully()
    {
        // Mock SetupStatusService to throw an exception
        $this->mock(SetupStatusService::class, function ($mock) {
            $mock->shouldReceive('getQueueWorkerStatus')
                ->andThrow(new \Exception('Cache service unavailable'));
        });

        $response = $this->getJson('/setup/queue-worker/status');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'QUEUE_STATUS_FAILED'
                ]
            ]);
    }

    /** @test */
    public function test_queue_endpoint_handles_dispatch_failures()
    {
        // Mock QueueTestService to throw an exception
        $this->mock(QueueTestService::class, function ($mock) {
            $mock->shouldReceive('dispatchTestJobWithStatus')
                ->andThrow(new \Exception('Failed to dispatch job'));
        });

        $response = $this->postJson('/setup/queue/test');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false
            ]);

        $this->assertStringContainsString('Failed to dispatch test job', $response->json('message'));
    }

    /** @test */
    public function endpoints_are_accessible_when_setup_enabled()
    {
        // Ensure setup is enabled
        config(['app.setup_enabled' => true]);

        // Test that endpoints are accessible when setup is enabled
        $response = $this->getJson('/setup/queue-worker/status');
        $response->assertStatus(200);

        $response = $this->postJson('/setup/status/refresh');
        $response->assertStatus(200);
    }

    /** @test */
    public function post_endpoints_work_with_proper_csrf_token()
    {
        // Test POST endpoints with proper CSRF token (Laravel handles this in tests)
        $response = $this->postJson('/setup/status/refresh');
        $response->assertStatus(200);

        $response = $this->postJson('/setup/queue/test');
        $response->assertStatus(200);
    }

    /** @test */
    public function queue_worker_status_response_includes_all_required_fields()
    {
        $response = $this->getJson('/setup/queue-worker/status');

        $response->assertStatus(200);

        $queueWorkerData = $response->json('data.queue_worker');
        
        $requiredFields = [
            'status', 'message', 'details', 'checked_at', 
            'step_name', 'priority', 'can_retry'
        ];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $queueWorkerData, 
                "Queue worker status response missing required field: {$field}");
        }
    }

    /** @test */
    public function setup_status_refresh_response_structure_is_consistent()
    {
        $response = $this->postJson('/setup/status/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'statuses' => [
                        '*' => [
                            'status',
                            'message',
                            'details',
                            'checked_at',
                            'step_name',
                            'priority',
                            'can_retry'
                        ]
                    ],
                    'summary' => [
                        'overall_status',
                        'completion_percentage',
                        'completed_steps',
                        'total_steps',
                        'incomplete_steps',
                        'error_steps',
                        'last_updated'
                    ],
                    'refreshed_at',
                    'request_id',
                    'duration_ms'
                ],
                'message'
            ]);
    }
}