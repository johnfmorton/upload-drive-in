<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\CloudStorageLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CloudStorageLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageLogService $logService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logService = new CloudStorageLogService();
        $this->user = User::factory()->create();
        
        // Clear cache before each test
        Cache::flush();
    }

    public function test_logs_token_refresh_attempt(): void
    {
        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('info')
            ->with('Token refresh attempt initiated', \Mockery::type('array'))
            ->once();

        $this->logService->logTokenRefreshAttempt($this->user, 'google-drive', [
            'trigger' => 'proactive_validation'
        ]);

        // Verify metrics are tracked
        $this->assertEquals(1, $this->getMetric('token_refresh_attempts.google-drive'));
        $this->assertEquals(1, $this->getMetric("token_refresh_attempts.google-drive.user.{$this->user->id}"));
    }

    public function test_logs_token_refresh_success(): void
    {
        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('info')
            ->with('Token refresh completed successfully', \Mockery::type('array'))
            ->once();

        $this->logService->logTokenRefreshSuccess($this->user, 'google-drive', [
            'new_expires_at' => now()->addHour()->toISOString()
        ]);

        // Verify metrics are tracked
        $this->assertEquals(1, $this->getMetric('token_refresh_success.google-drive'));
        $this->assertEquals(1, $this->getMetric("token_refresh_success.google-drive.user.{$this->user->id}"));
    }

    public function test_logs_token_refresh_failure(): void
    {
        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('error')
            ->with('Token refresh failed', \Mockery::type('array'))
            ->once();

        $this->logService->logTokenRefreshFailure($this->user, 'google-drive', 'Invalid refresh token', [
            'error_type' => 'invalid_credentials'
        ]);

        // Verify metrics are tracked
        $this->assertEquals(1, $this->getMetric('token_refresh_failures.google-drive'));
        $this->assertEquals(1, $this->getMetric("token_refresh_failures.google-drive.user.{$this->user->id}"));
    }

    public function test_logs_status_determination(): void
    {
        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('info')
            ->with('Status determined', \Mockery::type('array'))
            ->once();

        $this->logService->logStatusDetermination(
            $this->user, 
            'google-drive', 
            'healthy', 
            'Token is valid and API connectivity confirmed',
            ['determination_time_ms' => 150.5]
        );

        // Verify status frequency metrics
        $this->assertEquals(1, $this->getMetric('status_frequency.google-drive.healthy'));
        $this->assertEquals(1, $this->getMetric("status_frequency.google-drive.healthy.user.{$this->user->id}"));
    }

    public function test_logs_api_connectivity_test_success(): void
    {
        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('info')
            ->with('API connectivity test passed', \Mockery::type('array'))
            ->once();

        $this->logService->logApiConnectivityTest($this->user, 'google-drive', true, [
            'test_method' => 'about_get'
        ]);

        // Verify metrics are tracked
        $this->assertEquals(1, $this->getMetric('api_connectivity_success.google-drive'));
        $this->assertEquals(1, $this->getMetric("api_connectivity_success.google-drive.user.{$this->user->id}"));
    }

    public function test_logs_api_connectivity_test_failure(): void
    {
        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('warning')
            ->with('API connectivity test failed', \Mockery::type('array'))
            ->once();

        $this->logService->logApiConnectivityTest($this->user, 'google-drive', false, [
            'reason' => 'network_error'
        ]);

        // Verify metrics are tracked
        $this->assertEquals(1, $this->getMetric('api_connectivity_failures.google-drive'));
        $this->assertEquals(1, $this->getMetric("api_connectivity_failures.google-drive.user.{$this->user->id}"));
    }

    public function test_logs_proactive_token_validation(): void
    {
        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('info')
            ->with('Proactive token validation completed', \Mockery::type('array'))
            ->once();

        $this->logService->logProactiveTokenValidation($this->user, 'google-drive', true, true, true);

        // Verify proactive validation metrics
        $this->assertEquals(1, $this->getMetric('proactive_validation.expired_tokens.google-drive'));
        $this->assertEquals(1, $this->getMetric('proactive_validation.refresh_needed.google-drive'));
    }

    public function test_logs_cache_operations(): void
    {
        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('debug')
            ->with('Cache operation', \Mockery::type('array'))
            ->once();

        $this->logService->logCacheOperation('get', 'test_key', true, [
            'operation' => 'token_validation_cache_hit'
        ]);

        // Verify cache metrics
        $this->assertEquals(1, $this->getMetric('cache_hits'));
    }

    public function test_calculates_token_refresh_success_rate(): void
    {
        // Set up metrics
        $this->setMetric('token_refresh_attempts.google-drive', 10);
        $this->setMetric('token_refresh_success.google-drive', 8);

        $successRate = $this->logService->getTokenRefreshSuccessRate('google-drive');

        $this->assertEquals(0.8, $successRate);
    }

    public function test_returns_perfect_success_rate_when_no_attempts(): void
    {
        $successRate = $this->logService->getTokenRefreshSuccessRate('google-drive');

        $this->assertEquals(1.0, $successRate);
    }

    public function test_gets_status_distribution(): void
    {
        // Set up status metrics
        $this->setMetric('status_frequency.google-drive.healthy', 5);
        $this->setMetric('status_frequency.google-drive.authentication_required', 2);
        $this->setMetric('status_frequency.google-drive.connection_issues', 1);

        $distribution = $this->logService->getStatusDistribution('google-drive');

        $expected = [
            'healthy' => 5,
            'authentication_required' => 2,
            'connection_issues' => 1,
            'not_connected' => 0,
        ];

        $this->assertEquals($expected, $distribution);
    }

    public function test_gets_comprehensive_metrics_summary(): void
    {
        // Set up various metrics
        $this->setMetric('token_refresh_attempts.google-drive', 10);
        $this->setMetric('token_refresh_success.google-drive', 8);
        $this->setMetric('token_refresh_failures.google-drive', 2);
        $this->setMetric('api_connectivity_success.google-drive', 15);
        $this->setMetric('api_connectivity_failures.google-drive', 1);
        $this->setMetric('status_frequency.google-drive.healthy', 12);
        $this->setMetric('cache_hits', 50);
        $this->setMetric('cache_misses', 10);

        $summary = $this->logService->getMetricsSummary('google-drive');

        $this->assertArrayHasKey('token_refresh', $summary);
        $this->assertArrayHasKey('api_connectivity', $summary);
        $this->assertArrayHasKey('status_distribution', $summary);
        $this->assertArrayHasKey('cache_performance', $summary);

        $this->assertEquals(10, $summary['token_refresh']['attempts']);
        $this->assertEquals(8, $summary['token_refresh']['successes']);
        $this->assertEquals(2, $summary['token_refresh']['failures']);
        $this->assertEquals(0.8, $summary['token_refresh']['success_rate']);

        $this->assertEquals(15, $summary['api_connectivity']['successes']);
        $this->assertEquals(1, $summary['api_connectivity']['failures']);

        $this->assertEquals(12, $summary['status_distribution']['healthy']);

        $this->assertEquals(50, $summary['cache_performance']['hits']);
        $this->assertEquals(10, $summary['cache_performance']['misses']);
    }

    public function test_tracks_status_changes(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->times(3); // Two status determinations + one status change

        // First status determination
        $this->logService->logStatusDetermination($this->user, 'google-drive', 'healthy', 'All good');
        
        // Second status determination with different status
        $this->logService->logStatusDetermination($this->user, 'google-drive', 'authentication_required', 'Token expired');

        // Verify status change was tracked
        $this->assertEquals(1, $this->getMetric('status_changes.google-drive.healthy_to_authentication_required'));
    }

    public function test_resets_failure_count_on_success(): void
    {
        // Set up initial failure count
        $this->setMetric("token_refresh_failures.google-drive.user.{$this->user->id}", 3);

        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info');

        $this->logService->logTokenRefreshSuccess($this->user, 'google-drive');

        // Verify failure count was reset
        $this->assertNull($this->getMetric("token_refresh_failures.google-drive.user.{$this->user->id}"));
    }

    /**
     * Helper method to get a metric value from cache.
     */
    private function getMetric(string $metric): ?int
    {
        return Cache::get('cloud_storage_metrics:' . $metric);
    }

    /**
     * Helper method to set a metric value in cache.
     */
    private function setMetric(string $metric, int $value): void
    {
        Cache::put('cloud_storage_metrics:' . $metric, $value, 3600);
    }
}