<?php

namespace Tests\Feature;

use App\Jobs\TestQueueJob;
use App\Models\User;
use App\Services\QueueTestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Test the admin queue testing workflow functionality.
 * 
 * This test suite covers the complete workflow of testing queue functionality
 * from the admin dashboard, including job dispatch, status checking, and
 * health metrics retrieval.
 */
class AdminQueueTestingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
        ]);
        
        $this->regularUser = User::factory()->create([
            'role' => 'client',
            'email' => 'user@example.com',
        ]);
    }

    /**
     * Test that admin can dispatch a queue test job successfully.
     */
    public function test_admin_can_dispatch_queue_test_job(): void
    {
        Queue::fake();
        
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.queue.test'));
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Test job dispatched successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'test_job_id',
                'delay',
                'dispatched_at',
            ]);
        
        // Verify job was dispatched
        Queue::assertPushed(TestQueueJob::class);
        
        // Verify job ID format
        $jobId = $response->json('test_job_id');
        $this->assertMatchesRegularExpression('/^test_[a-f0-9\-]{36}$/', $jobId);
    }

    /**
     * Test that admin can dispatch a queue test job with delay.
     */
    public function test_admin_can_dispatch_queue_test_job_with_delay(): void
    {
        Queue::fake();
        
        $delay = 5;
        
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.queue.test'), [
                'delay' => $delay,
            ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'delay' => $delay,
            ]);
        
        Queue::assertPushed(TestQueueJob::class);
    }

    /**
     * Test validation for queue test job dispatch.
     */
    public function test_queue_test_job_dispatch_validation(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.queue.test'), [
                'delay' => -1, // Invalid negative delay
            ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['delay']);
        
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.queue.test'), [
                'delay' => 61, // Invalid delay > 60
            ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['delay']);
    }

    /**
     * Test that admin can check queue test job status.
     */
    public function test_admin_can_check_queue_test_job_status(): void
    {
        // Mock a test job status in cache
        $jobId = 'test_12345678-1234-1234-1234-123456789012';
        $mockStatus = [
            'test_job_id' => $jobId,
            'status' => 'completed',
            'message' => 'Test job completed successfully',
            'processing_time' => 1.23,
            'completed_at' => now()->toISOString(),
        ];
        
        Cache::put('test_queue_job_' . $jobId, $mockStatus, 3600);
        
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.queue.test.status', [
                'test_job_id' => $jobId,
            ]));
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => $mockStatus,
            ]);
    }

    /**
     * Test validation for queue test job status check.
     */
    public function test_queue_test_job_status_check_validation(): void
    {
        // Test missing job ID
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.queue.test.status'));
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['test_job_id']);
        
        // Test invalid job ID format
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.queue.test.status', [
                'test_job_id' => 'invalid-job-id',
            ]));
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['test_job_id']);
    }

    /**
     * Test that admin can get queue health metrics.
     */
    public function test_admin_can_get_queue_health_metrics(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.queue.health'));
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'metrics' => [
                    'timestamp',
                    'queue_tables_exist',
                    'job_statistics',
                    'stalled_jobs',
                    'test_job_statistics',
                    'overall_status',
                    'health_message',
                    'recommendations',
                ],
            ]);
    }

    /**
     * Test that non-admin users cannot access queue testing endpoints.
     */
    public function test_non_admin_cannot_access_queue_testing_endpoints(): void
    {
        // Test queue test dispatch - AdminMiddleware returns 403 for non-admin users
        $response = $this->actingAs($this->regularUser)
            ->postJson(route('admin.queue.test'));
        
        $response->assertStatus(403);
        
        // Test queue test status check
        $response = $this->actingAs($this->regularUser)
            ->getJson(route('admin.queue.test.status', [
                'test_job_id' => 'test_12345678-1234-1234-1234-123456789012',
            ]));
        
        $response->assertStatus(403);
        
        // Test queue health metrics
        $response = $this->actingAs($this->regularUser)
            ->getJson(route('admin.queue.health'));
        
        $response->assertStatus(403);
    }

    /**
     * Test that unauthenticated users cannot access queue testing endpoints.
     */
    public function test_unauthenticated_users_cannot_access_queue_testing_endpoints(): void
    {
        // Test queue test dispatch - AdminMiddleware redirects unauthenticated users
        $response = $this->postJson(route('admin.queue.test'));
        $response->assertStatus(401); // JSON requests return 401 instead of redirect
        
        // Test queue test status check
        $response = $this->getJson(route('admin.queue.test.status', [
            'test_job_id' => 'test_12345678-1234-1234-1234-123456789012',
        ]));
        $response->assertStatus(401);
        
        // Test queue health metrics
        $response = $this->getJson(route('admin.queue.health'));
        $response->assertStatus(401);
    }

    /**
     * Test error handling when QueueTestService throws exceptions.
     */
    public function test_error_handling_when_service_throws_exceptions(): void
    {
        // Mock QueueTestService to throw exception
        $this->mock(QueueTestService::class, function ($mock) {
            $mock->shouldReceive('dispatchTestJob')
                ->andThrow(new \Exception('Service error'));
        });
        
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.queue.test'));
        
        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to dispatch test job',
            ]);
    }

    /**
     * Test complete queue testing workflow from dispatch to completion.
     */
    public function test_complete_queue_testing_workflow(): void
    {
        Queue::fake();
        
        // Step 1: Dispatch test job
        $dispatchResponse = $this->actingAs($this->adminUser)
            ->postJson(route('admin.queue.test'));
        
        $dispatchResponse->assertStatus(200);
        $jobId = $dispatchResponse->json('test_job_id');
        
        // Step 2: Check initial status (should be pending since we're using Queue::fake())
        $statusResponse = $this->actingAs($this->adminUser)
            ->getJson(route('admin.queue.test.status', [
                'test_job_id' => $jobId,
            ]));
        
        $statusResponse->assertStatus(200);
        $status = $statusResponse->json('status');
        $this->assertEquals('pending', $status['status']);
        
        // Step 3: Simulate job completion by updating cache
        $completedStatus = [
            'test_job_id' => $jobId,
            'status' => 'completed',
            'message' => 'Test job completed successfully',
            'processing_time' => 0.5,
            'completed_at' => now()->toISOString(),
        ];
        
        Cache::put('test_queue_job_' . $jobId, $completedStatus, 3600);
        
        // Step 4: Check completed status
        $finalStatusResponse = $this->actingAs($this->adminUser)
            ->getJson(route('admin.queue.test.status', [
                'test_job_id' => $jobId,
            ]));
        
        $finalStatusResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => $completedStatus,
            ]);
        
        // Step 5: Get queue health metrics
        $healthResponse = $this->actingAs($this->adminUser)
            ->getJson(route('admin.queue.health'));
        
        $healthResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        
        // Verify the job was dispatched
        Queue::assertPushed(TestQueueJob::class);
    }

    /**
     * Test concurrent queue test prevention (if implemented).
     */
    public function test_multiple_concurrent_queue_tests(): void
    {
        Queue::fake();
        
        // Dispatch first test job
        $response1 = $this->actingAs($this->adminUser)
            ->postJson(route('admin.queue.test'));
        
        $response1->assertStatus(200);
        
        // Dispatch second test job (should also succeed as we allow multiple tests)
        $response2 = $this->actingAs($this->adminUser)
            ->postJson(route('admin.queue.test'));
        
        $response2->assertStatus(200);
        
        // Verify both jobs have different IDs
        $this->assertNotEquals(
            $response1->json('test_job_id'),
            $response2->json('test_job_id')
        );
        
        // Verify both jobs were dispatched
        Queue::assertPushed(TestQueueJob::class, 2);
    }

    /**
     * Test queue health metrics with various queue states.
     */
    public function test_queue_health_metrics_with_different_states(): void
    {
        // Test with empty queue (initial state)
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.queue.health'));
        
        $response->assertStatus(200);
        $metrics = $response->json('metrics');
        
        $this->assertArrayHasKey('queue_tables_exist', $metrics);
        $this->assertArrayHasKey('job_statistics', $metrics);
        $this->assertArrayHasKey('overall_status', $metrics);
        
        // Verify job statistics structure
        $jobStats = $metrics['job_statistics'];
        $this->assertArrayHasKey('pending_jobs', $jobStats);
        $this->assertArrayHasKey('failed_jobs_total', $jobStats);
        $this->assertArrayHasKey('failed_jobs_24h', $jobStats);
        $this->assertArrayHasKey('failed_jobs_1h', $jobStats);
    }
}