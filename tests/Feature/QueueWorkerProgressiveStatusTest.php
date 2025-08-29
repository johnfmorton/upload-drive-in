<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\QueueTestService;
use App\Services\QueueWorkerStatus;
use App\Jobs\TestQueueJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class QueueWorkerProgressiveStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /** @test */
    public function it_shows_progressive_status_updates_during_queue_worker_test()
    {
        Queue::fake();

        // Test the dispatch endpoint
        $response = $this->postJson('/setup/queue/test', [
            'delay' => 0
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'test_job_id',
                'message'
            ]);

        $testJobId = $response->json('test_job_id');

        // Check that initial status is cached
        $queueTestService = app(QueueTestService::class);
        $cachedStatus = $queueTestService->getCachedQueueWorkerStatus();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_TESTING, $cachedStatus->status);
        $this->assertEquals($testJobId, $cachedStatus->testJobId);
        $this->assertStringContains('queued', strtolower($cachedStatus->message));

        Queue::assertPushed(TestQueueJob::class);
    }

    /** @test */
    public function it_provides_status_endpoint_for_progressive_updates()
    {
        $queueTestService = app(QueueTestService::class);
        $jobId = 'test_' . uniqid();

        // Set up a testing status
        $testingStatus = QueueWorkerStatus::testing($jobId, 'Test job processing...');
        $queueTestService->cacheQueueWorkerStatus($testingStatus);

        // Mock the job status check
        Cache::put("test_queue_job_{$jobId}", [
            'test_job_id' => $jobId,
            'status' => 'processing',
            'message' => 'Test job is being processed',
            'started_at' => now()->toISOString(),
        ], 3600);

        $response = $this->getJson("/setup/queue/test/status?test_job_id={$jobId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => [
                    'test_job_id' => $jobId,
                    'status' => 'processing'
                ]
            ]);
    }

    /** @test */
    public function it_handles_completed_job_status_with_processing_time()
    {
        $queueTestService = app(QueueTestService::class);
        $jobId = 'test_' . uniqid();
        $processingTime = 1.75;

        // Simulate completed job
        Cache::put("test_queue_job_{$jobId}", [
            'test_job_id' => $jobId,
            'status' => 'completed',
            'processing_time' => $processingTime,
            'completed_at' => now()->toISOString(),
            'success' => true,
        ], 3600);

        $response = $this->getJson("/setup/queue/test/status?test_job_id={$jobId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => [
                    'test_job_id' => $jobId,
                    'status' => 'completed',
                    'processing_time' => $processingTime
                ]
            ]);
    }

    /** @test */
    public function it_handles_failed_job_status_with_error_message()
    {
        $queueTestService = app(QueueTestService::class);
        $jobId = 'test_' . uniqid();
        $errorMessage = 'Test operation failed';

        // Simulate failed job
        Cache::put("test_queue_job_{$jobId}", [
            'test_job_id' => $jobId,
            'status' => 'failed',
            'error_message' => $errorMessage,
            'failed_at' => now()->toISOString(),
        ], 3600);

        $response = $this->getJson("/setup/queue/test/status?test_job_id={$jobId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => [
                    'test_job_id' => $jobId,
                    'status' => 'failed',
                    'error_message' => $errorMessage
                ]
            ]);
    }

    /** @test */
    public function it_handles_timeout_scenario_for_long_running_jobs()
    {
        $queueTestService = app(QueueTestService::class);
        $jobId = 'test_' . uniqid();

        // Simulate job that has been pending for too long
        $oldTimestamp = now()->subMinutes(2)->toISOString();
        Cache::put("test_queue_job_{$jobId}", [
            'test_job_id' => $jobId,
            'status' => 'pending',
            'dispatched_at' => $oldTimestamp,
            'timeout_at' => now()->subMinute()->toISOString(), // Already timed out
        ], 3600);

        $response = $this->getJson("/setup/queue/test/status?test_job_id={$jobId}");

        $response->assertStatus(200);
        
        $status = $response->json('status');
        $this->assertEquals('timeout', $status['status']);
        $this->assertStringContains('timed out', strtolower($status['message']));
    }

    /** @test */
    public function it_returns_not_found_for_invalid_job_id()
    {
        $invalidJobId = 'invalid_job_id';

        $response = $this->getJson("/setup/queue/test/status?test_job_id={$invalidJobId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => [
                    'test_job_id' => $invalidJobId,
                    'status' => 'not_found'
                ]
            ]);
    }

    /** @test */
    public function it_integrates_progressive_updates_with_general_status_refresh()
    {
        Queue::fake();

        // Test the general status refresh endpoint
        $response = $this->postJson('/setup/status/refresh');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'data' => [
                    'statuses' => [
                        'database',
                        'mail',
                        'google_drive',
                        'migrations',
                        'admin_user'
                        // Note: queue_worker should NOT be in general status refresh
                    ]
                ]
            ]);

        // Queue worker should not be included in general status refresh
        $statuses = $response->json('data.statuses');
        $this->assertArrayNotHasKey('queue_worker', $statuses);
    }

    /** @test */
    public function it_provides_cached_queue_worker_status_endpoint()
    {
        $queueTestService = app(QueueTestService::class);
        
        // Set up a completed status
        $completedStatus = QueueWorkerStatus::completed(2.1, 'test_123');
        $queueTestService->cacheQueueWorkerStatus($completedStatus);

        $response = $this->getJson('/setup/queue-worker/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'queue_worker' => [
                        'status' => 'completed',
                        'processing_time' => 2.1,
                        'test_job_id' => 'test_123'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_returns_not_tested_status_when_no_cache_exists()
    {
        // Ensure no cached status exists
        Cache::forget(QueueWorkerStatus::CACHE_KEY);

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
    public function it_handles_expired_cached_status()
    {
        $queueTestService = app(QueueTestService::class);
        
        // Create an expired completed status (older than 1 hour)
        $expiredTime = now()->subHours(2);
        $expiredStatus = new QueueWorkerStatus(
            status: QueueWorkerStatus::STATUS_COMPLETED,
            message: 'Queue worker is functioning properly (1.5s)',
            testCompletedAt: $expiredTime,
            processingTime: 1.5,
            testJobId: 'test_expired'
        );
        
        $queueTestService->cacheQueueWorkerStatus($expiredStatus);

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
    public function it_validates_required_test_job_id_parameter()
    {
        $response = $this->getJson('/setup/queue/test/status');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['test_job_id']);
    }

    /** @test */
    public function it_validates_test_job_id_format()
    {
        $response = $this->getJson('/setup/queue/test/status?test_job_id=invalid_format');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => [
                    'status' => 'invalid',
                    'message' => 'Invalid job ID format'
                ]
            ]);
    }

    /** @test */
    public function it_logs_progressive_status_updates()
    {
        Queue::fake();
        Log::fake();

        // Dispatch a test job
        $this->postJson('/setup/queue/test', ['delay' => 0]);

        // Should log the progressive status updates
        Log::assertLogged('info', function ($message, $context) {
            return str_contains($message, 'Queue worker test initiated with progressive status updates');
        });
    }

    /** @test */
    public function it_handles_concurrent_queue_worker_tests()
    {
        Queue::fake();

        // Dispatch first test
        $response1 = $this->postJson('/setup/queue/test', ['delay' => 0]);
        $jobId1 = $response1->json('test_job_id');

        // Dispatch second test (should work independently)
        $response2 = $this->postJson('/setup/queue/test', ['delay' => 0]);
        $jobId2 = $response2->json('test_job_id');

        $this->assertNotEquals($jobId1, $jobId2);

        // Both should be successful
        $response1->assertStatus(200)->assertJson(['success' => true]);
        $response2->assertStatus(200)->assertJson(['success' => true]);

        Queue::assertPushed(TestQueueJob::class, 2);
    }

    /** @test */
    public function it_provides_troubleshooting_information_on_failure()
    {
        $queueTestService = app(QueueTestService::class);
        $jobId = 'test_' . uniqid();

        // Simulate a failed status with troubleshooting info
        $failedStatus = QueueWorkerStatus::failed(
            'Queue worker not responding',
            $jobId,
            [
                'Check if queue worker is running: php artisan queue:work',
                'Verify queue configuration in .env file'
            ]
        );
        
        $queueTestService->cacheQueueWorkerStatus($failedStatus);

        $response = $this->getJson('/setup/queue-worker/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'queue_worker' => [
                        'status' => 'failed',
                        'error_message' => 'Queue worker not responding',
                        'troubleshooting' => [
                            'Check if queue worker is running: php artisan queue:work',
                            'Verify queue configuration in .env file'
                        ]
                    ]
                ]
            ]);
    }
}