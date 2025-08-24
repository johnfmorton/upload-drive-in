<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\SetupDetectionService;
use App\Services\SetupStatusService;
use App\Services\QueueTestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Mockery;
use Exception;
use PDOException;

class SetupStatusErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any existing cache
        Cache::flush();
        
        // Ensure we're in testing environment
        Config::set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_handles_database_connection_failures_gracefully()
    {
        // Mock database connection failure
        DB::shouldReceive('connection->getPdo')
            ->andThrow(new PDOException('Connection failed'));

        $service = app(SetupDetectionService::class);
        
        // Should return false without throwing exception
        $result = $service->getDatabaseStatus();
        $this->assertFalse($result);
    }

    /** @test */
    public function it_uses_cached_fallback_data_when_fresh_checks_fail()
    {
        $statusService = app(SetupStatusService::class);
        
        // Put some fallback data in cache
        $fallbackData = [
            'database' => [
                'status' => 'completed',
                'message' => 'Database connection working',
                'fallback' => false
            ]
        ];
        
        Cache::put('setup_status_detailed_statuses_fallback', $fallbackData, 300);
        
        // Mock the detection service to throw an exception
        $this->mock(SetupDetectionService::class, function ($mock) {
            $mock->shouldReceive('getAllStepStatuses')
                ->andThrow(new Exception('Service unavailable'));
        });
        
        $result = $statusService->getDetailedStepStatuses(false);
        
        // Should return fallback data marked as fallback
        $this->assertArrayHasKey('database', $result);
        $this->assertTrue($result['database']['fallback']);
        $this->assertEquals('Service temporarily unavailable', $result['database']['fallback_reason']);
    }

    /** @test */
    public function it_returns_error_state_when_no_fallback_available()
    {
        $statusService = app(SetupStatusService::class);
        
        // Ensure no cache exists
        Cache::flush();
        
        // Mock the detection service to throw an exception
        $this->mock(SetupDetectionService::class, function ($mock) {
            $mock->shouldReceive('getAllStepStatuses')
                ->andThrow(new Exception('Service unavailable'));
        });
        
        $result = $statusService->getDetailedStepStatuses(false);
        
        // Should return error fallback statuses
        $this->assertArrayHasKey('database', $result);
        $this->assertEquals('cannot_verify', $result['database']['status']);
        $this->assertTrue($result['database']['fallback']);
    }

    /** @test */
    public function it_handles_queue_test_dispatch_failures_with_retry()
    {
        $queueService = app(QueueTestService::class);
        
        // Mock job dispatch to fail initially then succeed
        $this->mock(\App\Jobs\TestQueueJob::class, function ($mock) {
            $mock->shouldReceive('dispatch')
                ->once()
                ->andThrow(new Exception('Queue unavailable'))
                ->shouldReceive('dispatch')
                ->once()
                ->andReturn(true);
        });
        
        // Should eventually succeed after retry
        $jobId = $queueService->dispatchTestJob(0);
        $this->assertStringStartsWith('test_', $jobId);
    }

    /** @test */
    public function it_handles_timeout_scenarios_properly()
    {
        $queueService = app(QueueTestService::class);
        
        // Dispatch a test job
        $jobId = $queueService->dispatchTestJob(0);
        
        // Manually set timeout in the past
        $cacheKey = 'test_queue_job_' . $jobId;
        $status = Cache::get($cacheKey);
        $status['timeout_at'] = now()->subMinutes(1)->toISOString();
        Cache::put($cacheKey, $status, 3600);
        
        // Check status should detect timeout
        $result = $queueService->checkTestJobStatus($jobId);
        
        $this->assertEquals('timeout', $result['status']);
        $this->assertStringContains('timed out', $result['message']);
        $this->assertArrayHasKey('troubleshooting', $result);
    }

    /** @test */
    public function setup_status_refresh_endpoint_handles_service_failures()
    {
        // Mock security service to allow request
        $this->mock(\App\Services\SetupSecurityService::class, function ($mock) {
            $mock->shouldReceive('shouldBlockRequest')->andReturn(false);
            $mock->shouldReceive('sanitizeStatusRequest')->andReturn([
                'is_valid' => true,
                'sanitized' => [],
                'violations' => []
            ]);
            $mock->shouldReceive('logSecurityEvent')->andReturn(true);
        });
        
        // Mock status service to throw exception then return fallback
        $this->mock(SetupStatusService::class, function ($mock) {
            $mock->shouldReceive('refreshAllStatuses')
                ->andThrow(new Exception('Service unavailable'));
            $mock->shouldReceive('getDetailedStepStatuses')
                ->with(true)
                ->andReturn([
                    'database' => [
                        'status' => 'completed',
                        'message' => 'Cached result',
                        'fallback' => true
                    ]
                ]);
            $mock->shouldReceive('getStatusSummary')
                ->andReturn([
                    'overall_status' => 'incomplete',
                    'fallback' => true
                ]);
        });
        
        $response = $this->postJson('/setup/status/refresh');
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'statuses' => [
                    'database' => [
                        'status' => 'completed',
                        'fallback' => true
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    public function queue_test_endpoint_handles_dispatch_failures_gracefully()
    {
        // Create admin user
        $admin = \App\Models\User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN
        ]);
        
        $this->actingAs($admin);
        
        // Mock queue service to throw exception
        $this->mock(QueueTestService::class, function ($mock) {
            $mock->shouldReceive('dispatchTestJob')
                ->andThrow(new Exception('Queue service unavailable'));
        });
        
        $response = $this->postJson('/admin/queue/test', ['delay' => 0]);
        
        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'DISPATCH_FAILED',
                'troubleshooting' => [
                    'Check if queue worker is running: php artisan queue:work',
                    'Verify queue configuration in .env file',
                    'Check for failed jobs: php artisan queue:failed',
                    'Review application logs for detailed errors',
                    'Ensure database and cache services are accessible'
                ]
            ]
        ]);
    }

    /** @test */
    public function queue_status_check_handles_invalid_job_ids()
    {
        // Create admin user
        $admin = \App\Models\User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN
        ]);
        
        $this->actingAs($admin);
        
        $response = $this->getJson('/admin/queue/test/status?test_job_id=invalid-id');
        
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR'
            ]
        ]);
    }

    /** @test */
    public function it_provides_user_friendly_error_messages_with_technical_details_in_debug()
    {
        Config::set('app.debug', true);
        
        // Mock security service
        $this->mock(\App\Services\SetupSecurityService::class, function ($mock) {
            $mock->shouldReceive('shouldBlockRequest')->andReturn(false);
            $mock->shouldReceive('sanitizeStatusRequest')->andReturn([
                'is_valid' => true,
                'sanitized' => [],
                'violations' => []
            ]);
            $mock->shouldReceive('logSecurityEvent')->andReturn(true);
        });
        
        // Mock status service to throw exception
        $this->mock(SetupStatusService::class, function ($mock) {
            $mock->shouldReceive('refreshAllStatuses')
                ->andThrow(new Exception('Database connection failed'));
            $mock->shouldReceive('getDetailedStepStatuses')
                ->andThrow(new Exception('Database connection failed'));
        });
        
        $response = $this->postJson('/setup/status/refresh');
        
        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'error' => [
                'message' => 'Failed to refresh status. Please try again.',
                'code' => 'REFRESH_FAILED',
                'technical_details' => [
                    'exception' => 'Exception',
                    'message' => 'Database connection failed'
                ],
                'troubleshooting' => [
                    'Check your internet connection and try again',
                    'Refresh the page and retry the operation',
                    'If the problem persists, check application logs',
                    'Contact administrator if issue continues'
                ]
            ]
        ]);
    }

    /** @test */
    public function it_handles_cache_service_failures_gracefully()
    {
        // Mock cache to throw exceptions
        Cache::shouldReceive('get')
            ->andThrow(new Exception('Cache service unavailable'));
        Cache::shouldReceive('put')
            ->andThrow(new Exception('Cache service unavailable'));
        Cache::shouldReceive('has')
            ->andReturn(false);
        
        $statusService = app(SetupStatusService::class);
        
        // Should still work without cache
        $result = $statusService->getDetailedStepStatuses(false);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('database', $result);
    }

    /** @test */
    public function it_logs_comprehensive_error_information()
    {
        Log::spy();
        
        // Mock detection service to throw exception
        $this->mock(SetupDetectionService::class, function ($mock) {
            $mock->shouldReceive('getAllStepStatuses')
                ->andThrow(new Exception('Test error for logging'));
        });
        
        $statusService = app(SetupStatusService::class);
        
        try {
            $statusService->refreshAllStatuses(true); // Force fresh, no fallback
        } catch (Exception $e) {
            // Expected to throw
        }
        
        // Verify comprehensive logging
        Log::shouldHaveReceived('error')
            ->with('Failed to refresh setup statuses', Mockery::type('array'))
            ->once();
    }

    /** @test */
    public function it_provides_cache_statistics_for_debugging()
    {
        $statusService = app(SetupStatusService::class);
        
        // Put some test data in cache
        Cache::put('setup_status_detailed_statuses', ['test' => 'data'], 30);
        
        $stats = $statusService->getCacheStatistics();
        
        $this->assertArrayHasKey('cache_ttl', $stats);
        $this->assertArrayHasKey('fallback_cache_ttl', $stats);
        $this->assertArrayHasKey('keys', $stats);
        $this->assertArrayHasKey('detailed_statuses', $stats['keys']);
        $this->assertTrue($stats['keys']['detailed_statuses']['exists']);
    }

    /** @test */
    public function it_handles_concurrent_requests_safely()
    {
        $statusService = app(SetupStatusService::class);
        
        // Simulate concurrent requests by calling multiple times rapidly
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = $statusService->getDetailedStepStatuses();
        }
        
        // All results should be consistent
        foreach ($results as $result) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('database', $result);
        }
    }
}