<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\AnalyzeTokenRefreshLogs;
use App\Services\TokenRefreshMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AnalyzeTokenRefreshLogsTest extends TestCase
{
    private TokenRefreshMonitoringService $monitoringService;
    private string $testLogFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->monitoringService = $this->createMock(TokenRefreshMonitoringService::class);
        $this->testLogFile = storage_path('logs/test_laravel.log');
        
        // Create test log directory if it doesn't exist
        if (!File::exists(dirname($this->testLogFile))) {
            File::makeDirectory(dirname($this->testLogFile), 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test log file
        if (File::exists($this->testLogFile)) {
            File::delete($this->testLogFile);
        }
        
        parent::tearDown();
    }

    /** @test */
    public function it_analyzes_token_refresh_logs_successfully()
    {
        $this->createTestLogFile();
        
        // Mock monitoring service
        $this->monitoringService
            ->expects($this->once())
            ->method('getLogAnalysisQueries')
            ->willReturn([
                'recent_failures' => [
                    'description' => 'Recent token refresh failures',
                    'query' => 'grep "token_refresh_failure" storage/logs/laravel.log | tail -50',
                    'log_filter' => ['event' => 'token_refresh_failure'],
                    'time_range' => '1 hour'
                ]
            ]);

        $this->app->instance(TokenRefreshMonitoringService::class, $this->monitoringService);

        // Override the log file path for testing
        $this->partialMock(AnalyzeTokenRefreshLogs::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods()
                 ->shouldReceive('parseLogEntries')
                 ->andReturn(collect([
                     [
                         'timestamp' => now()->subHour(),
                         'level' => 'info',
                         'message' => 'Token refresh operation started',
                         'context' => [
                             'event' => 'token_refresh_start',
                             'user_id' => 1,
                             'provider' => 'google-drive',
                             'operation_id' => 'test_op_1'
                         ],
                         'raw' => '[2024-01-01 12:00:00] local.INFO: Token refresh operation started'
                     ],
                     [
                         'timestamp' => now()->subHour(),
                         'level' => 'error',
                         'message' => 'Token refresh operation failed',
                         'context' => [
                             'event' => 'token_refresh_failure',
                             'user_id' => 1,
                             'provider' => 'google-drive',
                             'operation_id' => 'test_op_1',
                             'error_type' => 'invalid_refresh_token',
                             'duration_ms' => 2500
                         ],
                         'raw' => '[2024-01-01 12:00:01] local.ERROR: Token refresh operation failed'
                     ]
                 ]));
        });

        $this->artisan('token-refresh:analyze-logs', [
            '--provider' => 'google-drive',
            '--hours' => 24,
            '--format' => 'table'
        ])->assertExitCode(Command::SUCCESS);
    }

    /** @test */
    public function it_handles_missing_log_file_gracefully()
    {
        // Don't create log file
        
        $this->monitoringService
            ->method('getLogAnalysisQueries')
            ->willReturn([]);

        $this->app->instance(TokenRefreshMonitoringService::class, $this->monitoringService);

        $this->artisan('token-refresh:analyze-logs', [
            '--provider' => 'google-drive'
        ])->assertExitCode(Command::FAILURE);
    }

    /** @test */
    public function it_supports_different_output_formats()
    {
        $this->createTestLogFile();
        
        $this->monitoringService
            ->method('getLogAnalysisQueries')
            ->willReturn([]);

        $this->app->instance(TokenRefreshMonitoringService::class, $this->monitoringService);

        // Test JSON format
        $this->artisan('token-refresh:analyze-logs', [
            '--provider' => 'google-drive',
            '--format' => 'json'
        ])->assertExitCode(Command::SUCCESS);

        // Test CSV format
        $this->artisan('token-refresh:analyze-logs', [
            '--provider' => 'google-drive',
            '--format' => 'csv'
        ])->assertExitCode(Command::SUCCESS);
    }

    /** @test */
    public function it_exports_analysis_to_file_when_requested()
    {
        $this->createTestLogFile();
        $exportFile = storage_path('logs/test_export.json');
        
        $this->monitoringService
            ->method('getLogAnalysisQueries')
            ->willReturn([]);

        $this->app->instance(TokenRefreshMonitoringService::class, $this->monitoringService);

        $this->artisan('token-refresh:analyze-logs', [
            '--provider' => 'google-drive',
            '--export' => $exportFile
        ])->assertExitCode(Command::SUCCESS);

        $this->assertTrue(File::exists($exportFile));
        
        // Clean up
        File::delete($exportFile);
    }

    /** @test */
    public function it_parses_log_entries_correctly()
    {
        $logContent = $this->createSampleLogContent();
        File::put($this->testLogFile, $logContent);
        
        $command = new AnalyzeTokenRefreshLogs($this->monitoringService);
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('parseLogEntries');
        $method->setAccessible(true);
        
        $entries = $method->invoke($command, $this->testLogFile, 24);
        
        $this->assertGreaterThan(0, $entries->count());
        
        foreach ($entries as $entry) {
            $this->assertArrayHasKey('timestamp', $entry);
            $this->assertArrayHasKey('level', $entry);
            $this->assertArrayHasKey('message', $entry);
            $this->assertArrayHasKey('context', $entry);
            $this->assertArrayHasKey('raw', $entry);
        }
    }

    /** @test */
    public function it_identifies_token_refresh_log_entries()
    {
        $command = new AnalyzeTokenRefreshLogs($this->monitoringService);
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('isTokenRefreshLogEntry');
        $method->setAccessible(true);
        
        // Test positive cases
        $this->assertTrue($method->invoke($command, '[2024-01-01 12:00:00] local.INFO: Token refresh operation started'));
        $this->assertTrue($method->invoke($command, '[2024-01-01 12:00:00] local.ERROR: token_refresh_failure occurred'));
        $this->assertTrue($method->invoke($command, '[2024-01-01 12:00:00] local.INFO: health_validation performed'));
        $this->assertTrue($method->invoke($command, '[2024-01-01 12:00:00] local.INFO: api_connectivity_test completed'));
        
        // Test negative cases
        $this->assertFalse($method->invoke($command, '[2024-01-01 12:00:00] local.INFO: User logged in'));
        $this->assertFalse($method->invoke($command, '[2024-01-01 12:00:00] local.ERROR: Database connection failed'));
    }

    /** @test */
    public function it_analyzes_summary_statistics_correctly()
    {
        $entries = collect([
            [
                'timestamp' => now(),
                'level' => 'info',
                'context' => ['event' => 'token_refresh_success', 'user_id' => 1]
            ],
            [
                'timestamp' => now(),
                'level' => 'error',
                'context' => ['event' => 'token_refresh_failure', 'user_id' => 2]
            ],
            [
                'timestamp' => now(),
                'level' => 'info',
                'context' => ['event' => 'token_refresh_success', 'user_id' => 1]
            ]
        ]);
        
        $command = new AnalyzeTokenRefreshLogs($this->monitoringService);
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('analyzeSummary');
        $method->setAccessible(true);
        
        $summary = $method->invoke($command, $entries, 'google-drive');
        
        $this->assertEquals(3, $summary['total_entries']);
        $this->assertEquals(2, $summary['token_refresh_operations']);
        $this->assertEquals(2, $summary['successful_operations']);
        $this->assertEquals(1, $summary['failed_operations']);
        $this->assertEquals(1.0, $summary['success_rate']);
        $this->assertEquals(2, $summary['unique_users']);
    }

    /** @test */
    public function it_analyzes_error_patterns_correctly()
    {
        $entries = collect([
            [
                'timestamp' => now(),
                'level' => 'error',
                'context' => [
                    'event' => 'token_refresh_failure',
                    'error_type' => 'invalid_refresh_token',
                    'error_message' => 'Token is invalid',
                    'user_id' => 1
                ]
            ],
            [
                'timestamp' => now(),
                'level' => 'error',
                'context' => [
                    'event' => 'token_refresh_failure',
                    'error_type' => 'network_timeout',
                    'error_message' => 'Request timed out',
                    'user_id' => 2
                ]
            ],
            [
                'timestamp' => now(),
                'level' => 'error',
                'context' => [
                    'event' => 'token_refresh_failure',
                    'error_type' => 'invalid_refresh_token',
                    'error_message' => 'Another invalid token',
                    'user_id' => 3
                ]
            ]
        ]);
        
        $command = new AnalyzeTokenRefreshLogs($this->monitoringService);
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('analyzeErrorPatterns');
        $method->setAccessible(true);
        
        $errorPatterns = $method->invoke($command, $entries, 'google-drive');
        
        $this->assertEquals(3, $errorPatterns['total_errors']);
        $this->assertEquals('invalid_refresh_token', $errorPatterns['most_common_error']);
        
        $errorTypes = $errorPatterns['error_types'];
        $this->assertEquals(2, $errorTypes['invalid_refresh_token']['count']);
        $this->assertEquals(1, $errorTypes['network_timeout']['count']);
        $this->assertEqualsWithDelta(66.67, $errorTypes['invalid_refresh_token']['percentage'], 0.1);
        $this->assertEqualsWithDelta(33.33, $errorTypes['network_timeout']['percentage'], 0.1);
    }

    /** @test */
    public function it_analyzes_performance_metrics_correctly()
    {
        $entries = collect([
            [
                'timestamp' => now(),
                'context' => ['duration_ms' => 1000]
            ],
            [
                'timestamp' => now(),
                'context' => ['duration_ms' => 2000]
            ],
            [
                'timestamp' => now(),
                'context' => ['duration_ms' => 6000] // Slow operation
            ]
        ]);
        
        $command = new AnalyzeTokenRefreshLogs($this->monitoringService);
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('analyzePerformance');
        $method->setAccessible(true);
        
        $performance = $method->invoke($command, $entries, 'google-drive');
        
        $this->assertEquals(3000, $performance['average_duration_ms']);
        $this->assertEquals(2000, $performance['median_duration_ms']);
        $this->assertEquals(1000, $performance['min_duration_ms']);
        $this->assertEquals(6000, $performance['max_duration_ms']);
        $this->assertEquals(1, $performance['slow_operations_count']); // >5000ms
    }

    /** @test */
    public function it_provides_meaningful_recommendations()
    {
        $command = new AnalyzeTokenRefreshLogs($this->monitoringService);
        
        // Test high failure rate scenario
        $analysis = [
            'summary' => ['success_rate' => 0.8], // Below 90%
            'performance' => ['slow_operations_count' => 5],
            'error_patterns' => ['most_common_error' => 'network_timeout'],
            'user_impact' => ['users_with_failures' => 10]
        ];
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('showRecommendations');
        $method->setAccessible(true);
        
        // Capture output
        ob_start();
        $method->invoke($command, $analysis);
        $output = ob_get_clean();
        
        $this->assertStringContainsString('High failure rate detected', $output);
        $this->assertStringContainsString('Slow operations detected', $output);
        $this->assertStringContainsString('Most common error: network_timeout', $output);
        $this->assertStringContainsString('Multiple users affected', $output);
    }

    /**
     * Create a test log file with sample content
     */
    private function createTestLogFile(): void
    {
        $content = $this->createSampleLogContent();
        File::put($this->testLogFile, $content);
    }

    /**
     * Create sample log content for testing
     */
    private function createSampleLogContent(): string
    {
        return '[2024-01-01 12:00:00] local.INFO: Token refresh operation started {"event":"token_refresh_start","user_id":1,"provider":"google-drive","operation_id":"test_op_1"}' . "\n" .
               '[2024-01-01 12:00:01] local.INFO: Token refresh operation completed successfully {"event":"token_refresh_success","user_id":1,"provider":"google-drive","operation_id":"test_op_1","duration_ms":1500}' . "\n" .
               '[2024-01-01 12:01:00] local.INFO: Token refresh operation started {"event":"token_refresh_start","user_id":2,"provider":"google-drive","operation_id":"test_op_2"}' . "\n" .
               '[2024-01-01 12:01:02] local.ERROR: Token refresh operation failed {"event":"token_refresh_failure","user_id":2,"provider":"google-drive","operation_id":"test_op_2","error_type":"invalid_refresh_token","duration_ms":2000}' . "\n" .
               '[2024-01-01 12:02:00] local.INFO: Health validation performed {"event":"health_validation","user_id":1,"provider":"google-drive","cache_hit":true,"validation_result":true}' . "\n" .
               '[2024-01-01 12:03:00] local.INFO: API connectivity test performed {"event":"api_connectivity_test","user_id":1,"provider":"google-drive","success":true,"response_time_ms":250}' . "\n" .
               '[2024-01-01 12:04:00] local.INFO: User logged in {"user_id":1}' . "\n"; // Non-token-refresh entry
    }
}