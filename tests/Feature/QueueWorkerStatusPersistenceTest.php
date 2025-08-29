<?php

namespace Tests\Feature;

use App\Services\QueueTestService;
use App\Services\QueueWorkerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Carbon\Carbon;

class QueueWorkerStatusPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private QueueTestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QueueTestService();
        
        // Clear any existing cache
        Cache::forget(QueueWorkerStatus::CACHE_KEY);
    }

    protected function tearDown(): void
    {
        // Clean up cache after each test
        Cache::forget(QueueWorkerStatus::CACHE_KEY);
        parent::tearDown();
    }

    /** @test */
    public function queue_worker_status_persists_across_page_loads_when_not_expired()
    {
        // Simulate a successful test result from 30 minutes ago
        $thirtyMinutesAgo = Carbon::now()->subMinutes(30);
        $successfulStatus = new QueueWorkerStatus(
            status: QueueWorkerStatus::STATUS_COMPLETED,
            message: 'Queue worker is functioning properly',
            testCompletedAt: $thirtyMinutesAgo,
            processingTime: 1.45,
            testJobId: 'test_12345'
        );

        // Cache the status
        $this->service->cacheQueueWorkerStatus($successfulStatus);

        // Simulate page load - get cached status
        $response = $this->getJson('/setup/queue-worker/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'queue_worker' => [
                        'status' => 'completed',
                        'message' => 'Queue worker is functioning properly',
                        'details' => [
                            'processing_time' => 1.45,
                            'test_job_id' => 'test_12345'
                        ]
                    ]
                ]
            ]);

        // Verify the test_completed_at is within expected range
        $responseData = $response->json('data.queue_worker');
        $testCompletedAt = Carbon::parse($responseData['test_completed_at']);
        $this->assertTrue($testCompletedAt->diffInMinutes(Carbon::now()) <= 35);
    }

    /** @test */
    public function queue_worker_status_shows_default_message_when_no_cache_exists()
    {
        // Ensure no cache exists
        Cache::forget(QueueWorkerStatus::CACHE_KEY);

        // Simulate page load - get status when no cache
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
    public function queue_worker_status_shows_default_message_when_cache_is_expired()
    {
        // Simulate an expired test result from 2 hours ago
        $twoHoursAgo = Carbon::now()->subHours(2);
        $expiredStatus = new QueueWorkerStatus(
            status: QueueWorkerStatus::STATUS_COMPLETED,
            message: 'Queue worker is functioning properly',
            testCompletedAt: $twoHoursAgo,
            processingTime: 2.1,
            testJobId: 'test_expired'
        );

        // Cache the expired status
        $this->service->cacheQueueWorkerStatus($expiredStatus);

        // Simulate page load - should return default message for expired cache
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

        // Verify cache was cleared
        $this->assertNull(Cache::get(QueueWorkerStatus::CACHE_KEY));
    }

    /** @test */
    public function failed_queue_worker_status_persists_and_allows_retry()
    {
        // Simulate a failed test result from 15 minutes ago
        $fifteenMinutesAgo = Carbon::now()->subMinutes(15);
        $failedStatus = new QueueWorkerStatus(
            status: QueueWorkerStatus::STATUS_FAILED,
            message: 'Queue worker test failed',
            testCompletedAt: $fifteenMinutesAgo,
            errorMessage: 'Job execution timeout',
            testJobId: 'test_failed',
            canRetry: true
        );

        // Cache the failed status
        $this->service->cacheQueueWorkerStatus($failedStatus);

        // Simulate page load - should show failed status with retry option
        $response = $this->getJson('/setup/queue-worker/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'queue_worker' => [
                        'status' => 'failed',
                        'message' => 'Queue worker test failed',
                        'details' => [
                            'error' => 'Job execution timeout',
                            'test_job_id' => 'test_failed'
                        ],
                        'can_retry' => true
                    ]
                ]
            ]);
    }

    /** @test */
    public function timeout_queue_worker_status_persists_with_appropriate_message()
    {
        // Simulate a timeout test result from 45 minutes ago
        $fortyFiveMinutesAgo = Carbon::now()->subMinutes(45);
        $timeoutStatus = new QueueWorkerStatus(
            status: QueueWorkerStatus::STATUS_TIMEOUT,
            message: 'Queue worker test timed out',
            testCompletedAt: $fortyFiveMinutesAgo,
            errorMessage: 'Test timed out after 30 seconds',
            testJobId: 'test_timeout',
            canRetry: true
        );

        // Cache the timeout status
        $this->service->cacheQueueWorkerStatus($timeoutStatus);

        // Simulate page load - should show timeout status
        $response = $this->getJson('/setup/queue-worker/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'queue_worker' => [
                        'status' => 'timeout',
                        'message' => 'Queue worker test timed out',
                        'details' => [
                            'error' => 'Test timed out after 30 seconds',
                            'test_job_id' => 'test_timeout'
                        ],
                        'can_retry' => true
                    ]
                ]
            ]);
    }

    /** @test */
    public function queue_worker_status_endpoint_handles_cache_corruption_gracefully()
    {
        // Simulate corrupted cache data (not an array)
        Cache::put(QueueWorkerStatus::CACHE_KEY, 'invalid_data', 3600);

        // Simulate page load - should handle corruption gracefully
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
    public function queue_worker_status_cache_expiration_boundary_conditions()
    {
        // Test status that's exactly 1 hour old (should be expired)
        $exactlyOneHourAgo = Carbon::now()->subHour();
        $boundaryStatus = new QueueWorkerStatus(
            status: QueueWorkerStatus::STATUS_COMPLETED,
            message: 'Queue worker is functioning properly',
            testCompletedAt: $exactlyOneHourAgo,
            processingTime: 0.95,
            testJobId: 'test_boundary'
        );

        $this->service->cacheQueueWorkerStatus($boundaryStatus);

        // Should be treated as expired
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

        // Test status that's 59 minutes old (should not be expired)
        $fiftyNineMinutesAgo = Carbon::now()->subMinutes(59);
        $validStatus = new QueueWorkerStatus(
            status: QueueWorkerStatus::STATUS_COMPLETED,
            message: 'Queue worker is functioning properly',
            testCompletedAt: $fiftyNineMinutesAgo,
            processingTime: 1.2,
            testJobId: 'test_valid'
        );

        $this->service->cacheQueueWorkerStatus($validStatus);

        $response = $this->getJson('/setup/queue-worker/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'queue_worker' => [
                        'status' => 'completed',
                        'message' => 'Queue worker is functioning properly',
                        'details' => [
                            'processing_time' => 1.2
                        ]
                    ]
                ]
            ]);
    }
}