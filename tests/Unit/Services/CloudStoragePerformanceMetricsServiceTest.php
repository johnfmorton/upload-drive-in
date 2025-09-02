<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CloudStoragePerformanceMetricsService;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CloudStoragePerformanceMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    private CloudStoragePerformanceMetricsService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CloudStoragePerformanceMetricsService();
        $this->user = User::factory()->create();
    }

    public function test_records_operation_metrics_successfully()
    {
        Log::shouldReceive('channel')->with('performance')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $this->service->recordOperationMetrics(
            'google-drive',
            'upload',
            $this->user,
            1500.0,
            true
        );

        // Verify metrics were cached
        $hour = now()->format('Y-m-d-H');
        $totalKey = "cloud_storage_perf:hourly:google-drive:upload:total_operations:{$hour}";
        $successKey = "cloud_storage_perf:hourly:google-drive:upload:successful_operations:{$hour}";
        
        $this->assertEquals(1, Cache::get($totalKey));
        $this->assertEquals(1, Cache::get($successKey));
    }

    public function test_records_failed_operation_metrics()
    {
        Log::shouldReceive('channel')->with('performance')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $this->service->recordOperationMetrics(
            'google-drive',
            'upload',
            $this->user,
            2500.0,
            false,
            'network_error'
        );

        $hour = now()->format('Y-m-d-H');
        $totalKey = "cloud_storage_perf:hourly:google-drive:upload:total_operations:{$hour}";
        $failedKey = "cloud_storage_perf:hourly:google-drive:upload:failed_operations:{$hour}";
        $errorKey = "cloud_storage_perf:hourly:google-drive:upload:errors.network_error:{$hour}";
        
        $this->assertEquals(1, Cache::get($totalKey));
        $this->assertEquals(1, Cache::get($failedKey));
        $this->assertEquals(1, Cache::get($errorKey));
    }

    public function test_records_file_operation_metrics()
    {
        Log::shouldReceive('channel')->with('performance')->andReturnSelf();
        Log::shouldReceive('info')->twice(); // Once for file operation, once for general operation

        $fileSizeBytes = 1024 * 1024; // 1MB
        $durationMs = 2000.0; // 2 seconds

        $this->service->recordFileOperationMetrics(
            'google-drive',
            'upload',
            $this->user,
            $fileSizeBytes,
            $durationMs,
            true
        );

        // Verify duration was recorded
        $hour = now()->format('Y-m-d-H');
        $durationKey = "cloud_storage_perf:durations:google-drive:upload:{$hour}";
        $durations = Cache::get($durationKey, []);
        
        $this->assertContains($durationMs, $durations);
    }

    public function test_gets_performance_summary()
    {
        // Set up some test data
        $hour = now()->format('Y-m-d-H');
        Cache::put("cloud_storage_perf:hourly:google-drive:upload:total_operations:{$hour}", 10);
        Cache::put("cloud_storage_perf:hourly:google-drive:upload:successful_operations:{$hour}", 8);
        Cache::put("cloud_storage_perf:hourly:google-drive:upload:failed_operations:{$hour}", 2);

        $summary = $this->service->getPerformanceSummary('google-drive', 1);

        $this->assertEquals('google-drive', $summary['provider']);
        $this->assertEquals(1, $summary['time_range']['hours']);
        $this->assertArrayHasKey('operations', $summary);
        $this->assertArrayHasKey('overall', $summary);
    }

    public function test_gets_operation_metrics()
    {
        // Set up test data
        $hour = now()->format('Y-m-d-H');
        Cache::put("cloud_storage_perf:hourly:google-drive:upload:total_operations:{$hour}", 5);
        Cache::put("cloud_storage_perf:hourly:google-drive:upload:successful_operations:{$hour}", 4);
        Cache::put("cloud_storage_perf:hourly:google-drive:upload:failed_operations:{$hour}", 1);

        $metrics = $this->service->getOperationMetrics('google-drive', 'upload', 1);

        $this->assertEquals('upload', $metrics['operation']);
        $this->assertEquals(5, $metrics['total_operations']);
        $this->assertEquals(4, $metrics['successful_operations']);
        $this->assertEquals(1, $metrics['failed_operations']);
        $this->assertEquals(80.0, $metrics['success_rate']);
    }

    public function test_calculates_provider_health_score()
    {
        // Set up test data for good performance
        $hour = now()->format('Y-m-d-H');
        Cache::put("cloud_storage_perf:hourly:google-drive:upload:total_operations:{$hour}", 100);
        Cache::put("cloud_storage_perf:hourly:google-drive:upload:successful_operations:{$hour}", 98);
        Cache::put("cloud_storage_perf:hourly:google-drive:upload:failed_operations:{$hour}", 2);

        $healthScore = $this->service->getProviderHealthScore('google-drive', 1);

        $this->assertEquals('google-drive', $healthScore['provider']);
        $this->assertIsFloat($healthScore['health_score']);
        $this->assertGreaterThanOrEqual(0, $healthScore['health_score']);
        $this->assertLessThanOrEqual(100, $healthScore['health_score']);
        $this->assertArrayHasKey('grade', $healthScore);
        $this->assertArrayHasKey('factors', $healthScore);
    }

    public function test_detects_performance_alerts()
    {
        // Set up test data for poor performance
        $hour = now()->format('Y-m-d-H');
        Cache::put("cloud_storage_perf:hourly:google-drive:upload:total_operations:{$hour}", 100);
        Cache::put("cloud_storage_perf:hourly:google-drive:upload:successful_operations:{$hour}", 80);
        Cache::put("cloud_storage_perf:hourly:google-drive:upload:failed_operations:{$hour}", 20);

        $alerts = $this->service->getPerformanceAlerts('google-drive', 1);

        $this->assertIsArray($alerts);
        // Should have alerts for low success rate
        $alertTypes = array_column($alerts, 'type');
        $this->assertContains('low_success_rate', $alertTypes);
    }

    public function test_tracks_duration_metrics()
    {
        $hour = now()->format('Y-m-d-H');
        $durationKey = "cloud_storage_perf:durations:google-drive:upload:{$hour}";
        
        // Record multiple durations
        $durations = [1000.0, 1500.0, 2000.0, 1200.0, 1800.0];
        foreach ($durations as $duration) {
            $this->service->recordOperationMetrics(
                'google-drive',
                'upload',
                $this->user,
                $duration,
                true
            );
        }

        $cachedDurations = Cache::get($durationKey, []);
        
        foreach ($durations as $duration) {
            $this->assertContains($duration, $cachedDurations);
        }
    }

    public function test_limits_cached_durations_to_prevent_memory_issues()
    {
        $hour = now()->format('Y-m-d-H');
        $durationKey = "cloud_storage_perf:durations:google-drive:upload:{$hour}";
        
        // Pre-populate cache with 1000 durations
        $existingDurations = array_fill(0, 1000, 1000.0);
        Cache::put($durationKey, $existingDurations);
        
        // Add one more duration
        $this->service->recordOperationMetrics(
            'google-drive',
            'upload',
            $this->user,
            2000.0,
            true
        );

        $cachedDurations = Cache::get($durationKey, []);
        
        // Should still be 1000 items (oldest removed)
        $this->assertCount(1000, $cachedDurations);
        $this->assertContains(2000.0, $cachedDurations);
    }

    public function test_calculates_health_grades_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getHealthGrade');
        $method->setAccessible(true);

        $this->assertEquals('A', $method->invoke($this->service, 98));
        $this->assertEquals('B', $method->invoke($this->service, 88));
        $this->assertEquals('C', $method->invoke($this->service, 78));
        $this->assertEquals('D', $method->invoke($this->service, 68));
        $this->assertEquals('F', $method->invoke($this->service, 58));
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}