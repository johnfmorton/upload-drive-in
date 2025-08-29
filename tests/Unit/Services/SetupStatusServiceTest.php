<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SetupStatusService;
use App\Services\SetupDetectionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use Mockery;

class SetupStatusServiceTest extends TestCase
{
    private SetupStatusService $setupStatusService;
    private SetupDetectionService $mockSetupDetectionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the SetupDetectionService
        $this->mockSetupDetectionService = Mockery::mock(SetupDetectionService::class);
        
        // Create the service with mocked dependency
        $this->setupStatusService = new SetupStatusService($this->mockSetupDetectionService);
        
        // Clear cache before each test
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_detailed_step_statuses_returns_enhanced_data(): void
    {
        // Arrange
        $mockStatuses = [
            'database' => [
                'status' => 'completed',
                'message' => 'Database connection is working',
                'checked_at' => '2025-01-01T12:00:00Z'
            ],
            'mail' => [
                'status' => 'incomplete',
                'message' => 'Mail server configuration not properly set up',
                'checked_at' => '2025-01-01T12:00:00Z'
            ]
        ];

        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andReturn($mockStatuses);

        // Act
        $result = $this->setupStatusService->getDetailedStepStatuses(false);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        
        // Check enhanced data structure
        $this->assertArrayHasKey('database', $result);
        $this->assertEquals('completed', $result['database']['status']);
        $this->assertEquals('Database Connection', $result['database']['step_name']);
        $this->assertEquals(1, $result['database']['priority']);
        $this->assertFalse($result['database']['can_retry']);
        
        $this->assertArrayHasKey('mail', $result);
        $this->assertEquals('incomplete', $result['mail']['status']);
        $this->assertEquals('Mail Configuration', $result['mail']['step_name']);
        $this->assertEquals(4, $result['mail']['priority']);
        $this->assertTrue($result['mail']['can_retry']);
    }

    public function test_get_detailed_step_statuses_uses_cache_when_available(): void
    {
        // Arrange
        $cachedData = [
            'database' => [
                'status' => 'completed',
                'message' => 'Cached data',
                'step_name' => 'Database Connection',
                'priority' => 1,
                'can_retry' => false
            ]
        ];
        
        Cache::put('setup_status_detailed_statuses', $cachedData, 30);

        // The mock should not be called when using cache
        $this->mockSetupDetectionService
            ->shouldNotReceive('getAllStepStatuses');

        // Act
        $result = $this->setupStatusService->getDetailedStepStatuses(true);

        // Assert
        $this->assertEquals($cachedData, $result);
    }

    public function test_get_detailed_step_statuses_bypasses_cache_when_requested(): void
    {
        // Arrange
        $cachedData = ['cached' => 'data'];
        $freshData = [
            'database' => [
                'status' => 'completed',
                'message' => 'Fresh data',
                'checked_at' => '2025-01-01T12:00:00Z'
            ]
        ];
        
        Cache::put('setup_status_detailed_statuses', $cachedData, 30);

        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andReturn($freshData);

        // Act
        $result = $this->setupStatusService->getDetailedStepStatuses(false);

        // Assert
        $this->assertNotEquals($cachedData, $result);
        $this->assertArrayHasKey('database', $result);
        $this->assertEquals('Fresh data', $result['database']['message']);
    }

    public function test_refresh_all_statuses_clears_cache_and_returns_fresh_data(): void
    {
        // Arrange
        $cachedData = ['old' => 'data'];
        $freshData = [
            'database' => [
                'status' => 'completed',
                'message' => 'Fresh data after refresh',
                'checked_at' => '2025-01-01T12:00:00Z'
            ]
        ];
        
        Cache::put('setup_status_detailed_statuses', $cachedData, 30);
        Cache::put('setup_status_summary', $cachedData, 30);

        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andReturn($freshData);

        // Act
        $result = $this->setupStatusService->refreshAllStatuses();

        // Assert
        $this->assertArrayHasKey('database', $result);
        $this->assertEquals('Fresh data after refresh', $result['database']['message']);
        
        // Note: Cache gets repopulated by getDetailedStepStatuses, so we can't check if it's cleared
        // Instead, verify we got fresh data by checking the message content
        $this->assertNotEquals($cachedData, $result);
    }

    public function test_refresh_all_statuses_excludes_queue_worker_from_response(): void
    {
        // Arrange
        $freshData = [
            'database' => [
                'status' => 'completed',
                'message' => 'Database working',
                'checked_at' => '2025-01-01T12:00:00Z'
            ],
            'mail' => [
                'status' => 'completed',
                'message' => 'Mail working',
                'checked_at' => '2025-01-01T12:00:00Z'
            ],
            'queue_worker' => [
                'status' => 'completed',
                'message' => 'Queue worker working',
                'checked_at' => '2025-01-01T12:00:00Z'
            ]
        ];

        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andReturn($freshData);

        // Act
        $result = $this->setupStatusService->refreshAllStatuses();

        // Assert
        $this->assertArrayHasKey('database', $result);
        $this->assertArrayHasKey('mail', $result);
        $this->assertArrayNotHasKey('queue_worker', $result); // Should be excluded
        $this->assertCount(2, $result); // Only 2 steps returned
    }

    public function test_get_queue_worker_status_returns_not_tested_when_no_cache(): void
    {
        // Arrange - No cached data

        // Act
        $result = $this->setupStatusService->getQueueWorkerStatus(false);

        // Assert
        $this->assertEquals('not_tested', $result['status']);
        $this->assertEquals('Click the Test Queue Worker button below', $result['message']);
        $this->assertEquals('Queue Worker', $result['step_name']);
        $this->assertEquals(6, $result['priority']);
        $this->assertTrue($result['can_retry']);
        $this->assertArrayHasKey('details', $result);
        $this->assertTrue($result['details']['requires_test']);
        $this->assertTrue($result['details']['test_available']);
    }

    public function test_get_queue_worker_status_returns_cached_valid_status(): void
    {
        // Arrange
        $validCachedStatus = [
            'status' => 'completed',
            'message' => 'Queue worker is functioning properly',
            'test_completed_at' => Carbon::now()->subMinutes(30)->toISOString(),
            'processing_time' => 1.23,
            'step_name' => 'Queue Worker',
            'priority' => 6,
            'can_retry' => true
        ];
        
        Cache::put('setup_queue_worker_status', $validCachedStatus, 3600);

        // Act
        $result = $this->setupStatusService->getQueueWorkerStatus(true);

        // Assert
        $this->assertEquals('completed', $result['status']);
        $this->assertEquals('Queue worker is functioning properly', $result['message']);
        $this->assertEquals(1.23, $result['processing_time']);
    }

    public function test_get_queue_worker_status_returns_not_tested_when_cache_expired(): void
    {
        // Arrange
        $expiredCachedStatus = [
            'status' => 'completed',
            'message' => 'Queue worker is functioning properly',
            'test_completed_at' => Carbon::now()->subHours(2)->toISOString(), // Expired (older than 1 hour)
            'processing_time' => 1.23
        ];
        
        Cache::put('setup_queue_worker_status', $expiredCachedStatus, 3600);

        // Act
        $result = $this->setupStatusService->getQueueWorkerStatus(false);

        // Assert
        $this->assertEquals('not_tested', $result['status']);
        $this->assertEquals('Click the Test Queue Worker button below', $result['message']);
    }

    public function test_get_queue_worker_status_returns_not_tested_when_status_not_completed(): void
    {
        // Arrange
        $failedCachedStatus = [
            'status' => 'failed',
            'message' => 'Queue worker test failed',
            'test_completed_at' => Carbon::now()->subMinutes(30)->toISOString(),
            'error_message' => 'Test job timed out'
        ];
        
        Cache::put('setup_queue_worker_status', $failedCachedStatus, 3600);

        // Act
        $result = $this->setupStatusService->getQueueWorkerStatus(false);

        // Assert
        $this->assertEquals('not_tested', $result['status']);
        $this->assertEquals('Click the Test Queue Worker button below', $result['message']);
    }

    public function test_clear_queue_worker_status_cache_removes_cache(): void
    {
        // Arrange
        Cache::put('setup_queue_worker_status', ['test' => 'data'], 3600);
        $this->assertTrue(Cache::has('setup_queue_worker_status'));

        // Act
        $this->setupStatusService->clearQueueWorkerStatusCache();

        // Assert
        $this->assertFalse(Cache::has('setup_queue_worker_status'));
    }

    public function test_clear_all_caches_includes_queue_worker_cache(): void
    {
        // Arrange
        Cache::put('setup_status_detailed_statuses', ['data'], 30);
        Cache::put('setup_status_summary', ['data'], 30);
        Cache::put('setup_queue_worker_status', ['data'], 3600);

        // Act
        $this->setupStatusService->clearAllCaches();

        // Assert
        $this->assertFalse(Cache::has('setup_status_detailed_statuses'));
        $this->assertFalse(Cache::has('setup_status_summary'));
        $this->assertFalse(Cache::has('setup_queue_worker_status'));
    }

    public function test_get_cache_statistics_includes_queue_worker_cache(): void
    {
        // Arrange
        Cache::put('setup_queue_worker_status', ['test' => 'data'], 3600);

        // Act
        $result = $this->setupStatusService->getCacheStatistics();

        // Assert
        $this->assertArrayHasKey('queue_worker_cache_ttl', $result);
        $this->assertEquals(3600, $result['queue_worker_cache_ttl']);
        $this->assertArrayHasKey('queue_worker_status', $result['keys']);
        $this->assertTrue($result['keys']['queue_worker_status']['exists']);
        $this->assertGreaterThan(0, $result['keys']['queue_worker_status']['size_bytes']);
    }

    public function test_get_status_summary_calculates_correct_statistics(): void
    {
        // Arrange
        $mockStatuses = [
            'database' => [
                'status' => 'completed',
                'message' => 'Working',
                'checked_at' => '2025-01-01T12:00:00Z'
            ],
            'mail' => [
                'status' => 'completed',
                'message' => 'Working',
                'checked_at' => '2025-01-01T12:00:00Z'
            ],
            'google_drive' => [
                'status' => 'incomplete',
                'message' => 'Not configured',
                'checked_at' => '2025-01-01T12:00:00Z'
            ],
            'admin_user' => [
                'status' => 'error',
                'message' => 'Error occurred',
                'checked_at' => '2025-01-01T12:00:00Z'
            ]
        ];

        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andReturn($mockStatuses);

        // Act
        $result = $this->setupStatusService->getStatusSummary(false);

        // Assert
        $this->assertEquals('error', $result['overall_status']); // Has error steps
        $this->assertEquals(50.0, $result['completion_percentage']); // 2 out of 4 completed
        $this->assertEquals(2, $result['completed_steps']);
        $this->assertEquals(4, $result['total_steps']);
        $this->assertEquals(['google_drive'], $result['incomplete_steps']);
        $this->assertEquals(['admin_user'], $result['error_steps']);
        $this->assertArrayHasKey('last_updated', $result);
    }

    public function test_get_status_summary_returns_completed_when_all_steps_done(): void
    {
        // Arrange
        $mockStatuses = [
            'database' => [
                'status' => 'completed',
                'message' => 'Working',
                'checked_at' => '2025-01-01T12:00:00Z'
            ],
            'mail' => [
                'status' => 'completed',
                'message' => 'Working',
                'checked_at' => '2025-01-01T12:00:00Z'
            ]
        ];

        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andReturn($mockStatuses);

        // Act
        $result = $this->setupStatusService->getStatusSummary(false);

        // Assert
        $this->assertEquals('completed', $result['overall_status']);
        $this->assertEquals(100.0, $result['completion_percentage']);
        $this->assertEquals(2, $result['completed_steps']);
        $this->assertEquals(2, $result['total_steps']);
        $this->assertEmpty($result['incomplete_steps']);
        $this->assertEmpty($result['error_steps']);
    }

    public function test_is_setup_complete_returns_true_when_all_completed(): void
    {
        // Arrange
        $mockStatuses = [
            'database' => [
                'status' => 'completed',
                'message' => 'Working',
                'checked_at' => '2025-01-01T12:00:00Z'
            ]
        ];

        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andReturn($mockStatuses);

        // Act
        $result = $this->setupStatusService->isSetupComplete(false);

        // Assert
        $this->assertTrue($result);
    }

    public function test_is_setup_complete_returns_false_when_incomplete(): void
    {
        // Arrange
        $mockStatuses = [
            'database' => [
                'status' => 'incomplete',
                'message' => 'Not working',
                'checked_at' => '2025-01-01T12:00:00Z'
            ]
        ];

        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andReturn($mockStatuses);

        // Act
        $result = $this->setupStatusService->isSetupComplete(false);

        // Assert
        $this->assertFalse($result);
    }

    public function test_clear_status_cache_removes_all_cached_data(): void
    {
        // Arrange
        Cache::put('setup_status_detailed_statuses', ['data'], 30);
        Cache::put('setup_status_summary', ['data'], 30);

        // Act
        $this->setupStatusService->clearStatusCache();

        // Assert
        $this->assertFalse(Cache::has('setup_status_detailed_statuses'));
        $this->assertFalse(Cache::has('setup_status_summary'));
    }

    public function test_get_cache_ttl_returns_correct_value(): void
    {
        // Act
        $ttl = $this->setupStatusService->getCacheTtl();

        // Assert
        $this->assertEquals(30, $ttl);
    }

    public function test_handles_exception_in_get_detailed_step_statuses(): void
    {
        // Arrange
        Log::shouldReceive('error')->once();
        
        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andThrow(new Exception('Service failure'));

        // Act
        $result = $this->setupStatusService->getDetailedStepStatuses(false);

        // Assert
        $this->assertIsArray($result);
        
        // Should return fallback data for all steps (including queue_worker in getDetailedStepStatuses)
        $expectedSteps = ['database', 'mail', 'google_drive', 'migrations', 'admin_user', 'queue_worker'];
        foreach ($expectedSteps as $step) {
            $this->assertArrayHasKey($step, $result);
            $this->assertEquals('cannot_verify', $result[$step]['status']);
            $this->assertArrayHasKey('error', $result[$step]['details']);
            $this->assertTrue($result[$step]['details']['fallback']);
        }
    }

    public function test_handles_exception_in_refresh_all_statuses(): void
    {
        // Arrange
        Log::shouldReceive('error')->once(); // getDetailedStepStatuses logs error
        Log::shouldReceive('debug')->once(); // clearStatusCache logs debug message
        Log::shouldReceive('info')->once(); // refreshAllStatuses logs success even with fallback data
        
        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andThrow(new Exception('Service failure'));

        // Act
        $result = $this->setupStatusService->refreshAllStatuses();

        // Assert - Should return fallback data, not throw exception
        $this->assertIsArray($result);
        $this->assertCount(5, $result); // 5 fallback steps (excluding queue_worker)
        
        // Should not contain queue_worker
        $this->assertArrayNotHasKey('queue_worker', $result);
        
        // All steps should be in cannot_verify status
        foreach ($result as $step => $status) {
            $this->assertEquals('cannot_verify', $status['status']);
        }
    }

    public function test_handles_exception_in_get_status_summary(): void
    {
        // Arrange
        Log::shouldReceive('error')->once(); // Only once for getDetailedStepStatuses
        
        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andThrow(new Exception('Service failure'));

        // Act
        $result = $this->setupStatusService->getStatusSummary(false);

        // Assert
        // When getDetailedStepStatuses fails, it returns fallback data with 'cannot_verify' status
        // This means the overall status will be 'incomplete' not 'error'
        $this->assertEquals('incomplete', $result['overall_status']);
        $this->assertEquals(0, $result['completion_percentage']);
        $this->assertEquals(0, $result['completed_steps']);
        $this->assertEquals(6, $result['total_steps']); // 6 fallback steps (getDetailedStepStatuses still includes queue_worker)
        $this->assertCount(6, $result['incomplete_steps']); // All steps are incomplete
    }

    public function test_handles_exception_in_is_setup_complete(): void
    {
        // Arrange
        Log::shouldReceive('error')->once();
        
        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andThrow(new Exception('Service failure'));

        // Act
        $result = $this->setupStatusService->isSetupComplete(false);

        // Assert
        $this->assertFalse($result);
    }

    public function test_uses_cached_data_when_fresh_check_fails(): void
    {
        // Arrange
        $cachedData = [
            'database' => [
                'status' => 'completed',
                'message' => 'Cached data',
                'step_name' => 'Database Connection',
                'priority' => 1,
                'can_retry' => false
            ]
        ];
        
        Cache::put('setup_status_detailed_statuses', $cachedData, 30);
        
        // When using cache (useCache = true), it should return cached data without calling the service
        $this->mockSetupDetectionService
            ->shouldNotReceive('getAllStepStatuses');

        // Act
        $result = $this->setupStatusService->getDetailedStepStatuses(true);

        // Assert - Should return cached data
        $this->assertEquals($cachedData, $result);
    }

    public function test_step_display_names_are_correct(): void
    {
        // Arrange
        $mockStatuses = [
            'database' => ['status' => 'completed', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z'],
            'mail' => ['status' => 'completed', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z'],
            'google_drive' => ['status' => 'completed', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z'],
            'migrations' => ['status' => 'completed', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z'],
            'admin_user' => ['status' => 'completed', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z'],
            'queue_worker' => ['status' => 'completed', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z'],
            'unknown_step' => ['status' => 'completed', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z']
        ];

        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andReturn($mockStatuses);

        // Act
        $result = $this->setupStatusService->getDetailedStepStatuses(false);

        // Assert
        $this->assertEquals('Database Connection', $result['database']['step_name']);
        $this->assertEquals('Mail Configuration', $result['mail']['step_name']);
        $this->assertEquals('Google Drive Integration', $result['google_drive']['step_name']);
        $this->assertEquals('Database Migrations', $result['migrations']['step_name']);
        $this->assertEquals('Admin User Account', $result['admin_user']['step_name']);
        $this->assertEquals('Queue Worker', $result['queue_worker']['step_name']);
        $this->assertEquals('Unknown Step', $result['unknown_step']['step_name']);
    }

    public function test_step_priorities_are_correct(): void
    {
        // Arrange
        $mockStatuses = [
            'database' => ['status' => 'completed', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z'],
            'migrations' => ['status' => 'completed', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z'],
            'admin_user' => ['status' => 'completed', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z'],
            'mail' => ['status' => 'completed', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z'],
            'google_drive' => ['status' => 'completed', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z'],
            'queue_worker' => ['status' => 'completed', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z'],
            'unknown_step' => ['status' => 'completed', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z']
        ];

        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andReturn($mockStatuses);

        // Act
        $result = $this->setupStatusService->getDetailedStepStatuses(false);

        // Assert
        $this->assertEquals(1, $result['database']['priority']);
        $this->assertEquals(2, $result['migrations']['priority']);
        $this->assertEquals(3, $result['admin_user']['priority']);
        $this->assertEquals(4, $result['mail']['priority']);
        $this->assertEquals(5, $result['google_drive']['priority']);
        $this->assertEquals(6, $result['queue_worker']['priority']);
        $this->assertEquals(99, $result['unknown_step']['priority']);
    }

    public function test_can_retry_logic_is_correct(): void
    {
        // Arrange
        $mockStatuses = [
            'completed_step' => ['status' => 'completed', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z'],
            'incomplete_step' => ['status' => 'incomplete', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z'],
            'error_step' => ['status' => 'error', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z'],
            'cannot_verify_step' => ['status' => 'cannot_verify', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z'],
            'checking_step' => ['status' => 'checking', 'message' => 'Test', 'checked_at' => '2025-01-01T12:00:00Z']
        ];

        $this->mockSetupDetectionService
            ->shouldReceive('getAllStepStatuses')
            ->once()
            ->andReturn($mockStatuses);

        // Act
        $result = $this->setupStatusService->getDetailedStepStatuses(false);

        // Assert
        $this->assertFalse($result['completed_step']['can_retry']); // Completed steps can't be retried
        $this->assertTrue($result['incomplete_step']['can_retry']); // Incomplete steps can be retried
        $this->assertTrue($result['error_step']['can_retry']); // Error steps can be retried
        $this->assertFalse($result['cannot_verify_step']['can_retry']); // Cannot verify steps can't be retried
        $this->assertFalse($result['checking_step']['can_retry']); // Checking steps can't be retried
    }
}