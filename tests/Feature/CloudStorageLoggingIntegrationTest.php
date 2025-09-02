<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Services\CloudStorageLogService;
use App\Services\CloudStorageHealthService;
use App\Services\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Carbon\Carbon;

class CloudStorageLoggingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private CloudStorageLogService $logService;
    private CloudStorageHealthService $healthService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->logService = app(CloudStorageLogService::class);
        $this->healthService = app(CloudStorageHealthService::class);
        
        // Clear cache before each test
        Cache::flush();
    }

    public function test_comprehensive_logging_during_status_determination(): void
    {
        // Create an expired token to trigger refresh
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subHour(),
        ]);

        // Mock the Google Drive service to simulate token refresh failure
        $this->mock(GoogleDriveService::class, function ($mock) {
            $mock->shouldReceive('validateAndRefreshToken')
                ->once()
                ->andReturn(false);
        });

        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('debug')->atLeast()->once();
        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('error')->atLeast()->once(); // Add error expectation

        // Perform status determination which should trigger logging
        $status = $this->healthService->determineConsolidatedStatus($this->user, 'google-drive');

        // Verify status determination was logged
        $this->assertEquals('authentication_required', $status);
        
        // Verify metrics were tracked
        $this->assertGreaterThan(0, $this->getMetric('status_frequency.google-drive.authentication_required'));
    }

    public function test_token_refresh_logging_with_successful_refresh(): void
    {
        // Create an expired token
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subHour(),
        ]);

        // Mock successful token refresh
        $googleService = $this->mock(GoogleDriveService::class);
        $googleService->shouldReceive('validateAndRefreshToken')
            ->once()
            ->andReturnUsing(function ($user) {
                // Simulate logging that would happen in real service
                $this->logService->logTokenRefreshAttempt($user, 'google-drive', [
                    'trigger' => 'test'
                ]);
                $this->logService->logTokenRefreshSuccess($user, 'google-drive', [
                    'new_expires_at' => now()->addHour()->toISOString()
                ]);
                return true;
            });

        $googleService->shouldReceive('testApiConnectivity')
            ->once()
            ->andReturnUsing(function ($user) {
                $this->logService->logApiConnectivityTest($user, 'google-drive', true);
                return true;
            });

        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('debug')->atLeast()->once();
        Log::shouldReceive('info')->atLeast()->once();

        // Perform status determination
        $status = $this->healthService->determineConsolidatedStatus($this->user, 'google-drive');

        // Verify successful status and metrics
        $this->assertEquals('healthy', $status);
        $this->assertEquals(1, $this->getMetric('token_refresh_attempts.google-drive'));
        $this->assertEquals(1, $this->getMetric('token_refresh_success.google-drive'));
        $this->assertEquals(1, $this->getMetric('api_connectivity_success.google-drive'));
        $this->assertEquals(1, $this->getMetric('status_frequency.google-drive.healthy'));
    }

    public function test_cache_operations_are_logged(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('debug')->atLeast()->once();
        Log::shouldReceive('error')->atLeast()->once(); // Add error expectation for failed token validation

        // First call should miss cache and log cache miss
        $this->healthService->ensureValidToken($this->user, 'google-drive');
        
        // Verify cache miss was logged
        $this->assertEquals(1, $this->getMetric('cache_misses'));
    }

    public function test_api_connectivity_failure_logging(): void
    {
        // Create a valid token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        // Mock token validation success but API connectivity failure
        $googleService = $this->mock(GoogleDriveService::class);
        $googleService->shouldReceive('validateAndRefreshToken')
            ->once()
            ->andReturn(true);

        $googleService->shouldReceive('testApiConnectivity')
            ->once()
            ->andReturnUsing(function ($user) {
                $this->logService->logApiConnectivityTest($user, 'google-drive', false, [
                    'reason' => 'network_error'
                ]);
                return false;
            });

        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('debug')->atLeast()->once();
        Log::shouldReceive('info')->atLeast()->once(); // Add info expectation for status determination
        Log::shouldReceive('warning')->atLeast()->once(); // Add warning expectation for API connectivity failure

        // Perform status determination
        $status = $this->healthService->determineConsolidatedStatus($this->user, 'google-drive');

        // Verify connection issues status and metrics
        $this->assertEquals('connection_issues', $status);
        $this->assertEquals(1, $this->getMetric('api_connectivity_failures.google-drive'));
        $this->assertEquals(1, $this->getMetric('status_frequency.google-drive.connection_issues'));
    }

    public function test_status_change_tracking(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->atLeast()->twice();

        // First status determination
        $this->logService->logStatusDetermination($this->user, 'google-drive', 'healthy', 'All good');
        
        // Second status determination with different status
        $this->logService->logStatusDetermination($this->user, 'google-drive', 'authentication_required', 'Token expired');

        // Verify status change was tracked
        $this->assertEquals(1, $this->getMetric('status_changes.google-drive.healthy_to_authentication_required'));
    }

    public function test_metrics_summary_includes_all_categories(): void
    {
        // Set up various metrics
        $this->setMetric('token_refresh_attempts.google-drive', 5);
        $this->setMetric('token_refresh_success.google-drive', 4);
        $this->setMetric('token_refresh_failures.google-drive', 1);
        $this->setMetric('api_connectivity_success.google-drive', 8);
        $this->setMetric('api_connectivity_failures.google-drive', 2);
        $this->setMetric('status_frequency.google-drive.healthy', 6);
        $this->setMetric('status_frequency.google-drive.authentication_required', 1);
        $this->setMetric('cache_hits', 20);
        $this->setMetric('cache_misses', 5);

        $summary = $this->logService->getMetricsSummary('google-drive');

        // Verify all categories are present
        $this->assertArrayHasKey('token_refresh', $summary);
        $this->assertArrayHasKey('api_connectivity', $summary);
        $this->assertArrayHasKey('status_distribution', $summary);
        $this->assertArrayHasKey('cache_performance', $summary);

        // Verify token refresh metrics
        $this->assertEquals(5, $summary['token_refresh']['attempts']);
        $this->assertEquals(4, $summary['token_refresh']['successes']);
        $this->assertEquals(1, $summary['token_refresh']['failures']);
        $this->assertEquals(0.8, $summary['token_refresh']['success_rate']);

        // Verify API connectivity metrics
        $this->assertEquals(8, $summary['api_connectivity']['successes']);
        $this->assertEquals(2, $summary['api_connectivity']['failures']);

        // Verify status distribution
        $this->assertEquals(6, $summary['status_distribution']['healthy']);
        $this->assertEquals(1, $summary['status_distribution']['authentication_required']);
        $this->assertEquals(0, $summary['status_distribution']['connection_issues']);
        $this->assertEquals(0, $summary['status_distribution']['not_connected']);

        // Verify cache performance
        $this->assertEquals(20, $summary['cache_performance']['hits']);
        $this->assertEquals(5, $summary['cache_performance']['misses']);
    }

    public function test_proactive_token_validation_logging(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $this->logService->logProactiveTokenValidation($this->user, 'google-drive', true, true, true);

        // Verify proactive validation metrics
        $this->assertEquals(1, $this->getMetric('proactive_validation.expired_tokens.google-drive'));
        $this->assertEquals(1, $this->getMetric('proactive_validation.refresh_needed.google-drive'));
    }

    public function test_error_context_is_preserved_in_logs(): void
    {
        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('error')
            ->with('Token refresh failed', \Mockery::on(function ($data) {
                return isset($data['event']) && 
                       $data['event'] === 'token_refresh_failure' &&
                       isset($data['context']['error_type']) &&
                       $data['context']['error_type'] === 'invalid_credentials';
            }))
            ->once();

        $this->logService->logTokenRefreshFailure($this->user, 'google-drive', 'Invalid refresh token', [
            'error_type' => 'invalid_credentials',
            'http_code' => 400,
            'attempt' => 1
        ]);

        // Verify metrics are tracked
        $this->assertEquals(1, $this->getMetric('token_refresh_failures.google-drive'));
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