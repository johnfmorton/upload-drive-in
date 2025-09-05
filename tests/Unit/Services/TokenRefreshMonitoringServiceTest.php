<?php

namespace Tests\Unit\Services;

use App\Enums\TokenRefreshErrorType;
use App\Models\User;
use App\Services\TokenRefreshMonitoringService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TokenRefreshMonitoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private TokenRefreshMonitoringService $monitoringService;
    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->monitoringService = new TokenRefreshMonitoringService();
        $this->testUser = User::factory()->create();
        
        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_logs_refresh_operation_start_with_structured_format()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Token refresh operation started', \Mockery::on(function ($data) {
                return $this->validateLogStructure($data, [
                    'event' => 'token_refresh_start',
                    'user_id' => $this->testUser->id,
                    'provider' => 'google-drive',
                    'operation_id' => 'test_op_123',
                    'timestamp',
                    'context'
                ]);
            }));

        $this->monitoringService->logRefreshOperationStart(
            $this->testUser,
            'google-drive',
            'test_op_123',
            ['trigger' => 'manual_test']
        );
    }

    /** @test */
    public function it_logs_refresh_operation_success_with_performance_metrics()
    {
        // First log the start to establish timing
        $this->monitoringService->logRefreshOperationStart(
            $this->testUser,
            'google-drive',
            'test_op_123'
        );

        // Mock a small delay
        usleep(10000); // 10ms

        Log::shouldReceive('info')
            ->once()
            ->with('Token refresh operation completed successfully', \Mockery::on(function ($data) {
                return $this->validateLogStructure($data, [
                    'event' => 'token_refresh_success',
                    'user_id' => $this->testUser->id,
                    'provider' => 'google-drive',
                    'operation_id' => 'test_op_123',
                    'timestamp',
                    'duration_ms',
                    'context'
                ]) && $data['duration_ms'] > 0;
            }));

        $this->monitoringService->logRefreshOperationSuccess(
            $this->testUser,
            'google-drive',
            'test_op_123',
            ['was_already_valid' => false]
        );
    }

    /** @test */
    public function it_logs_refresh_operation_failure_with_error_classification()
    {
        $exception = new Exception('Invalid refresh token', 400);
        $errorType = TokenRefreshErrorType::INVALID_REFRESH_TOKEN;

        Log::shouldReceive('error')
            ->once()
            ->with('Token refresh operation failed', \Mockery::on(function ($data) use ($exception, $errorType) {
                return $this->validateLogStructure($data, [
                    'event' => 'token_refresh_failure',
                    'user_id' => $this->testUser->id,
                    'provider' => 'google-drive',
                    'operation_id' => 'test_op_123',
                    'timestamp',
                    'duration_ms',
                    'error_type' => $errorType->value,
                    'error_message' => $exception->getMessage(),
                    'error_code' => $exception->getCode(),
                    'error_class' => get_class($exception),
                    'is_recoverable' => $errorType->isRecoverable(),
                    'requires_user_intervention' => $errorType->requiresUserIntervention(),
                    'max_retry_attempts' => $errorType->getMaxRetryAttempts(),
                    'context'
                ]);
            }));

        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $this->monitoringService->logRefreshOperationFailure(
            $this->testUser,
            'google-drive',
            'test_op_123',
            $errorType,
            $exception,
            ['coordination_failure' => false]
        );
    }

    /** @test */
    public function it_logs_health_validation_with_cache_tracking()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Health validation performed', \Mockery::on(function ($data) {
                return $this->validateLogStructure($data, [
                    'event' => 'health_validation',
                    'user_id' => $this->testUser->id,
                    'provider' => 'google-drive',
                    'operation_id' => 'health_op_123',
                    'timestamp',
                    'cache_hit' => true,
                    'validation_result' => true,
                    'context'
                ]);
            }));

        $this->monitoringService->logHealthValidation(
            $this->testUser,
            'google-drive',
            'health_op_123',
            true, // cache hit
            true, // validation result
            ['cached_status' => 'healthy']
        );
    }

    /** @test */
    public function it_logs_api_connectivity_test_with_response_time()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('API connectivity test performed', \Mockery::on(function ($data) {
                return $this->validateLogStructure($data, [
                    'event' => 'api_connectivity_test',
                    'user_id' => $this->testUser->id,
                    'provider' => 'google-drive',
                    'operation_id' => 'api_test_123',
                    'timestamp',
                    'success' => true,
                    'response_time_ms' => 250,
                    'context'
                ]);
            }));

        $this->monitoringService->logApiConnectivityTest(
            $this->testUser,
            'google-drive',
            'api_test_123',
            true,
            250,
            ['test_method' => 'about_get']
        );
    }

    /** @test */
    public function it_tracks_success_metrics_correctly()
    {
        // Perform multiple successful operations
        for ($i = 0; $i < 5; $i++) {
            $operationId = "test_op_{$i}";
            $this->monitoringService->logRefreshOperationStart($this->testUser, 'google-drive', $operationId);
            $this->monitoringService->logRefreshOperationSuccess($this->testUser, 'google-drive', $operationId);
        }

        $metrics = $this->monitoringService->getPerformanceMetrics('google-drive', 1);

        $this->assertEquals(5, $metrics['refresh_operations']['total_operations']);
        $this->assertEquals(5, $metrics['refresh_operations']['successful_operations']);
        $this->assertEquals(0, $metrics['refresh_operations']['failed_operations']);
        $this->assertEquals(1.0, $metrics['refresh_operations']['success_rate']);
        $this->assertEquals(0.0, $metrics['refresh_operations']['failure_rate']);
    }

    /** @test */
    public function it_tracks_failure_metrics_with_error_breakdown()
    {
        $errorTypes = [
            TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
            TokenRefreshErrorType::NETWORK_TIMEOUT,
            TokenRefreshErrorType::INVALID_REFRESH_TOKEN, // Duplicate to test counting
        ];

        foreach ($errorTypes as $index => $errorType) {
            $operationId = "test_op_{$index}";
            $exception = new Exception("Test error {$index}");
            
            $this->monitoringService->logRefreshOperationStart($this->testUser, 'google-drive', $operationId);
            $this->monitoringService->logRefreshOperationFailure(
                $this->testUser,
                'google-drive',
                $operationId,
                $errorType,
                $exception
            );
        }

        $metrics = $this->monitoringService->getPerformanceMetrics('google-drive', 1);

        $this->assertEquals(3, $metrics['refresh_operations']['total_operations']);
        $this->assertEquals(0, $metrics['refresh_operations']['successful_operations']);
        $this->assertEquals(3, $metrics['refresh_operations']['failed_operations']);
        $this->assertEquals(0.0, $metrics['refresh_operations']['success_rate']);
        $this->assertEquals(1.0, $metrics['refresh_operations']['failure_rate']);

        // Check error breakdown
        $errorBreakdown = $metrics['refresh_operations']['error_breakdown'];
        $this->assertEquals(2, $errorBreakdown[TokenRefreshErrorType::INVALID_REFRESH_TOKEN->value]);
        $this->assertEquals(1, $errorBreakdown[TokenRefreshErrorType::NETWORK_TIMEOUT->value]);
    }

    /** @test */
    public function it_tracks_health_cache_metrics()
    {
        // Log cache hits and misses
        $this->monitoringService->logHealthValidation($this->testUser, 'google-drive', 'op1', true, true);
        $this->monitoringService->logHealthValidation($this->testUser, 'google-drive', 'op2', true, true);
        $this->monitoringService->logHealthValidation($this->testUser, 'google-drive', 'op3', false, true);

        $metrics = $this->monitoringService->getPerformanceMetrics('google-drive', 1);

        $this->assertEquals(3, $metrics['health_validation']['total_validations']);
        $this->assertEquals(2, $metrics['health_validation']['cache_hits']);
        $this->assertEquals(1, $metrics['health_validation']['cache_misses']);
        $this->assertEqualsWithDelta(0.6667, $metrics['health_validation']['cache_hit_rate'], 0.001);
        $this->assertEqualsWithDelta(0.3333, $metrics['health_validation']['cache_miss_rate'], 0.001);
    }

    /** @test */
    public function it_calculates_alerting_thresholds_correctly()
    {
        // Create scenario with high failure rate (above 10% threshold)
        for ($i = 0; $i < 8; $i++) {
            $operationId = "success_op_{$i}";
            $this->monitoringService->logRefreshOperationStart($this->testUser, 'google-drive', $operationId);
            $this->monitoringService->logRefreshOperationSuccess($this->testUser, 'google-drive', $operationId);
        }

        for ($i = 0; $i < 3; $i++) {
            $operationId = "failure_op_{$i}";
            $exception = new Exception("Test failure {$i}");
            $this->monitoringService->logRefreshOperationStart($this->testUser, 'google-drive', $operationId);
            $this->monitoringService->logRefreshOperationFailure(
                $this->testUser,
                'google-drive',
                $operationId,
                TokenRefreshErrorType::NETWORK_TIMEOUT,
                $exception
            );
        }

        $metrics = $this->monitoringService->getPerformanceMetrics('google-drive', 1);
        $alerts = $metrics['alerting_status']['active_alerts'];

        // Should have high failure rate alert (3/11 = 27.3% > 10%)
        $this->assertGreaterThan(0, count($alerts));
        $this->assertEquals('high_failure_rate', $alerts[0]['type']);
        $this->assertEquals('critical', $alerts[0]['severity']);
    }

    /** @test */
    public function it_calculates_system_health_indicators()
    {
        // Create healthy scenario
        for ($i = 0; $i < 10; $i++) {
            $operationId = "healthy_op_{$i}";
            $this->monitoringService->logRefreshOperationStart($this->testUser, 'google-drive', $operationId);
            usleep(1000); // 1ms delay
            $this->monitoringService->logRefreshOperationSuccess($this->testUser, 'google-drive', $operationId);
        }

        $metrics = $this->monitoringService->getPerformanceMetrics('google-drive', 1);
        $systemHealth = $metrics['system_health'];

        $this->assertEquals('healthy', $systemHealth['overall_status']);
        $this->assertEquals('healthy', $systemHealth['token_refresh_health']);
        $this->assertArrayHasKey('last_updated', $systemHealth);
    }

    /** @test */
    public function it_provides_log_analysis_queries()
    {
        $queries = $this->monitoringService->getLogAnalysisQueries();

        $this->assertIsArray($queries);
        $this->assertArrayHasKey('recent_failures', $queries);
        $this->assertArrayHasKey('error_patterns', $queries);
        $this->assertArrayHasKey('slow_operations', $queries);
        $this->assertArrayHasKey('user_specific_issues', $queries);
        $this->assertArrayHasKey('cache_performance', $queries);

        // Validate query structure
        foreach ($queries as $queryName => $queryData) {
            $this->assertArrayHasKey('description', $queryData);
            $this->assertArrayHasKey('query', $queryData);
            $this->assertIsString($queryData['description']);
            $this->assertIsString($queryData['query']);
        }
    }

    /** @test */
    public function it_resets_metrics_correctly()
    {
        Log::shouldReceive('info')->zeroOrMoreTimes();
        
        // Create some metrics
        $this->monitoringService->logRefreshOperationStart($this->testUser, 'google-drive', 'test_op');
        $this->monitoringService->logRefreshOperationSuccess($this->testUser, 'google-drive', 'test_op');

        // Verify metrics exist
        $metricsBefore = $this->monitoringService->getPerformanceMetrics('google-drive', 1);
        $this->assertGreaterThan(0, $metricsBefore['refresh_operations']['total_operations']);

        // Reset metrics
        $this->monitoringService->resetMetrics('google-drive');

        // Clear the cache to ensure fresh data
        Cache::flush();

        // Verify metrics are reset
        $metricsAfter = $this->monitoringService->getPerformanceMetrics('google-drive', 1);
        $this->assertEquals(0, $metricsAfter['refresh_operations']['total_operations']);
    }

    /** @test */
    public function it_handles_operation_duration_calculation_without_start_time()
    {
        // Log success without logging start (missing start time)
        Log::shouldReceive('info')->once();

        $this->monitoringService->logRefreshOperationSuccess(
            $this->testUser,
            'google-drive',
            'missing_start_op'
        );

        // Should not throw exception and should handle gracefully
        $this->assertTrue(true);
    }

    /** @test */
    public function it_maintains_consistent_log_format_across_all_operations()
    {
        $requiredFields = ['event', 'user_id', 'provider', 'operation_id', 'timestamp'];
        $loggedData = [];

        Log::shouldReceive('info')->andReturnUsing(function ($message, $data) use (&$loggedData) {
            $loggedData[] = $data;
        });

        Log::shouldReceive('error')->andReturnUsing(function ($message, $data) use (&$loggedData) {
            $loggedData[] = $data;
        });

        Log::shouldReceive('warning')->zeroOrMoreTimes();

        // Test all log methods
        $this->monitoringService->logRefreshOperationStart($this->testUser, 'google-drive', 'op1');
        $this->monitoringService->logRefreshOperationSuccess($this->testUser, 'google-drive', 'op2');
        $this->monitoringService->logRefreshOperationFailure(
            $this->testUser,
            'google-drive',
            'op3',
            TokenRefreshErrorType::NETWORK_TIMEOUT,
            new Exception('Test')
        );
        $this->monitoringService->logHealthValidation($this->testUser, 'google-drive', 'op4', true, true);
        $this->monitoringService->logApiConnectivityTest($this->testUser, 'google-drive', 'op5', true, 100);

        // Verify all logs have required fields
        foreach ($loggedData as $data) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey($field, $data, "Missing required field: {$field}");
            }
            
            // Verify timestamp format
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/',
                $data['timestamp'],
                'Invalid timestamp format'
            );
        }
    }

    /**
     * Helper method to validate log structure
     */
    private function validateLogStructure(array $data, array $expectedFields): bool
    {
        foreach ($expectedFields as $key => $value) {
            if (is_numeric($key)) {
                // Field name only (check existence)
                if (!array_key_exists($value, $data)) {
                    return false;
                }
            } else {
                // Field name and expected value
                if (!array_key_exists($key, $data) || $data[$key] !== $value) {
                    return false;
                }
            }
        }
        
        return true;
    }
}