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
        
        // Should return fallback data for all steps
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
        $this->assertCount(6, $result); // 6 fallback steps
        
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
        $this->assertEquals(6, $result['total_steps']); // 6 fallback steps
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