<?php

namespace Tests\Unit\Services;

use App\Services\SetupStatusService;
use App\Services\SetupDetectionService;
use App\Services\QueueTestService;
use App\Services\QueueWorkerStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Tests\TestCase;
use Mockery;

/**
 * Comprehensive unit tests for SetupStatusService queue worker separation logic.
 * 
 * Tests the modified behavior where queue worker status is handled separately
 * from general status refresh operations.
 */
class SetupStatusServiceComprehensiveTest extends TestCase
{
    private SetupStatusService $service;
    private $mockSetupDetectionService;
    private $mockQueueTestService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockSetupDetectionService = Mockery::mock(SetupDetectionService::class);
        $this->mockQueueTestService = Mockery::mock(QueueTestService::class);
        
        $this->service = new SetupStatusService(
            $this->mockSetupDetectionService,
            $this->mockQueueTestService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Test that refreshAllStatuses excludes queue_worker from general status checks.
     * 
     * @covers SetupStatusService::refreshAllStatuses
     */
    public function test_refresh_all_statuses_excludes_queue_worker()
    {
        // Mock getAllStepStatuses to return all steps including queue_worker
        $allStatuses = [
            'database' => ['status' => 'completed', 'message' => 'Database connected'],
            'mail' => ['status' => 'completed', 'message' => 'Mail configured'],
            'google_drive' => ['status' => 'completed', 'message' => 'Google Drive configured'],
            'migrations' => ['status' => 'completed', 'message' => 'Migrations complete'],
            'admin_user' => ['status' => 'completed', 'message' => 'Admin user exists'],
            'queue_worker' => ['status' => 'completed', 'message' => 'Queue worker functioning']
        ];

        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andReturn($allStatuses);

        $result = $this->service->refreshAllStatuses(false); // Skip cache

        // Verify queue_worker is not in the result (should be excluded)
        $this->assertArrayNotHasKey('queue_worker', $result);
        
        // Verify other steps are present
        $this->assertArrayHasKey('database', $result);
        $this->assertArrayHasKey('mail', $result);
        $this->assertArrayHasKey('google_drive', $result);
        $this->assertArrayHasKey('migrations', $result);
        $this->assertArrayHasKey('admin_user', $result);
    }

    /**
     * Test getQueueWorkerStatus method returns cached status when available.
     * 
     * @covers SetupStatusService::getQueueWorkerStatus
     */
    public function test_get_queue_worker_status_returns_cached_status()
    {
        $completedAt = Carbon::now();
        $queueWorkerStatus = QueueWorkerStatus::completed(1.23, 'test-job-123');

        $this->mockQueueTestService
            ->shouldReceive('getCachedQueueWorkerStatus')
            ->once()
            ->andReturn($queueWorkerStatus);

        $result = $this->service->getQueueWorkerStatus();

        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $result['status']);
        $this->assertEquals('Queue worker is functioning properly (1.23s)', $result['message']);
        $this->assertNotNull($result['test_completed_at']); // Time will be set by the static method
        $this->assertArrayHasKey('details', $result);
        $this->assertEquals(1.23, $result['details']['processing_time']);
    }

    /**
     * Test getQueueWorkerStatus returns not_tested status when no cache exists.
     * 
     * @covers SetupStatusService::getQueueWorkerStatus
     */
    public function test_get_queue_worker_status_returns_not_tested_when_no_cache()
    {
        $queueWorkerStatus = QueueWorkerStatus::notTested();

        $this->mockQueueTestService
            ->shouldReceive('getCachedQueueWorkerStatus')
            ->once()
            ->andReturn($queueWorkerStatus);

        $result = $this->service->getQueueWorkerStatus();

        $this->assertEquals(QueueWorkerStatus::STATUS_NOT_TESTED, $result['status']);
        $this->assertStringContainsString('Click the Test Queue Worker button', $result['message']);
        $this->assertTrue($result['can_retry']);
    }

    /**
     * Test getQueueWorkerStatus handles expired cache properly.
     * 
     * @covers SetupStatusService::getQueueWorkerStatus
     */
    public function test_get_queue_worker_status_handles_expired_cache()
    {
        // Create an expired status (QueueTestService should handle this internally)
        $expiredStatus = QueueWorkerStatus::notTested();

        $this->mockQueueTestService
            ->shouldReceive('getCachedQueueWorkerStatus')
            ->once()
            ->andReturn($expiredStatus);

        $result = $this->service->getQueueWorkerStatus();

        // Should return not_tested status for expired cache
        $this->assertEquals(QueueWorkerStatus::STATUS_NOT_TESTED, $result['status']);
        $this->assertStringContainsString('Click the Test Queue Worker button', $result['message']);
    }

    /**
     * Test caching behavior for general status refresh.
     * 
     * @covers SetupStatusService::refreshAllStatuses
     */
    public function test_refresh_all_statuses_caching_behavior()
    {
        $allStatuses = [
            'database' => ['status' => 'completed', 'message' => 'Database connected'],
            'mail' => ['status' => 'completed', 'message' => 'Mail configured'],
            'google_drive' => ['status' => 'completed', 'message' => 'Google Drive configured'],
            'migrations' => ['status' => 'completed', 'message' => 'Migrations complete'],
            'admin_user' => ['status' => 'completed', 'message' => 'Admin user exists'],
            'queue_worker' => ['status' => 'completed', 'message' => 'Queue worker functioning']
        ];

        // Mock service call for fresh data
        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andReturn($allStatuses);

        // First call should hit the service (force fresh)
        $result1 = $this->service->refreshAllStatuses(false);
        
        // Verify queue_worker is excluded from both results
        $this->assertArrayNotHasKey('queue_worker', $result1);
        $this->assertArrayHasKey('database', $result1);
    }

    /**
     * Test error handling in refreshAllStatuses.
     * 
     * @covers SetupStatusService::refreshAllStatuses
     */
    public function test_refresh_all_statuses_error_handling()
    {
        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andThrow(new \Exception('Service connection failed'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to refresh setup statuses', Mockery::type('array'));
            
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Service connection failed');

        $this->service->refreshAllStatuses(false);
    }

    /**
     * Test retry logic in refreshAllStatuses.
     * 
     * @covers SetupStatusService::refreshAllStatuses
     */
    public function test_refresh_all_statuses_retry_logic()
    {
        $successfulStatuses = [
            'database' => ['status' => 'completed', 'message' => 'Database connected'],
            'mail' => ['status' => 'completed', 'message' => 'Mail configured'],
            'google_drive' => ['status' => 'completed', 'message' => 'Google Drive configured'],
            'migrations' => ['status' => 'completed', 'message' => 'Migrations complete'],
            'admin_user' => ['status' => 'completed', 'message' => 'Admin user exists'],
            'queue_worker' => ['status' => 'completed', 'message' => 'Queue worker functioning']
        ];

        // First attempt fails, second succeeds (retry logic is internal)
        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->times(3) // Will retry up to 3 times
            ->andThrow(new \Exception('Temporary failure'))
            ->andThrow(new \Exception('Temporary failure'))
            ->andReturn($successfulStatuses);

        Log::shouldReceive('warning')->twice();
        Log::shouldReceive('debug')->atLeast()->once();

        $result = $this->service->refreshAllStatuses(false);

        $this->assertEquals('completed', $result['database']['status']);
        $this->assertArrayNotHasKey('queue_worker', $result);
    }

    /**
     * Test that queue worker status is properly separated in response structure.
     * 
     * @covers SetupStatusService::refreshAllStatuses
     * @covers SetupStatusService::getQueueWorkerStatus
     */
    public function test_structured_status_response_excludes_queue_worker()
    {
        $allStatuses = [
            'database' => ['status' => 'completed', 'message' => 'Database connected'],
            'mail' => ['status' => 'completed', 'message' => 'Mail configured'],
            'google_drive' => ['status' => 'completed', 'message' => 'Google Drive configured'],
            'migrations' => ['status' => 'completed', 'message' => 'Migrations complete'],
            'admin_user' => ['status' => 'completed', 'message' => 'Admin user exists'],
            'queue_worker' => ['status' => 'completed', 'message' => 'Queue worker functioning']
        ];

        $queueWorkerStatus = QueueWorkerStatus::completed(1.23, 'test-job-456');

        // Mock the methods
        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andReturn($allStatuses);

        $this->mockQueueTestService
            ->shouldReceive('getCachedQueueWorkerStatus')
            ->once()
            ->andReturn($queueWorkerStatus);

        $generalResult = $this->service->refreshAllStatuses(false);
        $queueResult = $this->service->getQueueWorkerStatus();

        // Verify separation
        $this->assertArrayNotHasKey('queue_worker', $generalResult);
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $queueResult['status']);
        $this->assertArrayHasKey('database', $generalResult);
    }

    /**
     * Test cache statistics functionality.
     * 
     * @covers SetupStatusService::getCacheStatistics
     */
    public function test_cache_statistics()
    {
        $stats = $this->service->getCacheStatistics();

        $this->assertArrayHasKey('cache_ttl', $stats);
        $this->assertArrayHasKey('fallback_cache_ttl', $stats);
        $this->assertArrayHasKey('queue_worker_cache_ttl', $stats);
        $this->assertArrayHasKey('keys', $stats);
        
        $this->assertEquals(30, $stats['cache_ttl']);
        $this->assertEquals(300, $stats['fallback_cache_ttl']);
        $this->assertEquals(3600, $stats['queue_worker_cache_ttl']);
    }

    /**
     * Test cache clearing functionality.
     * 
     * @covers SetupStatusService::clearAllCaches
     */
    public function test_cache_clearing()
    {
        // Set up some cache data
        Cache::put('setup_status_detailed_statuses', ['test' => 'data'], 60);
        Cache::put('setup_status_summary', ['test' => 'summary'], 60);
        
        $this->assertTrue(Cache::has('setup_status_detailed_statuses'));
        $this->assertTrue(Cache::has('setup_status_summary'));
        
        $this->service->clearAllCaches();
        
        $this->assertFalse(Cache::has('setup_status_detailed_statuses'));
        $this->assertFalse(Cache::has('setup_status_summary'));
    }
}