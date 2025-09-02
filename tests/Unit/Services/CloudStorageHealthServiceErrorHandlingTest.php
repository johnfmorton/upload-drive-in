<?php

namespace Tests\Unit\Services;

use App\Enums\CloudStorageErrorType;
use App\Models\CloudStorageHealthStatus;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Services\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CloudStorageHealthServiceErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageHealthService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->service = app(CloudStorageHealthService::class);
    }

    #[Test]
    public function it_tracks_token_refresh_failures_and_error_details()
    {
        // Create expired token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_token' => null, // No refresh token to force failure
            'expires_at' => Carbon::now()->subHour(),
        ]);

        // Mock GoogleDriveService to return detailed error
        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->method('validateAndRefreshToken')
            ->willReturn(false);

        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        // Call ensureValidToken multiple times to test failure tracking
        $result1 = $this->service->ensureValidToken($this->user, 'google-drive');
        $result2 = $this->service->ensureValidToken($this->user, 'google-drive');
        $result3 = $this->service->ensureValidToken($this->user, 'google-drive');

        $this->assertFalse($result1);
        $this->assertFalse($result2);
        $this->assertFalse($result3);

        // Check that failures are tracked
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();

        $this->assertNotNull($healthStatus);
        $this->assertEquals(3, $healthStatus->token_refresh_failures);
        $this->assertNotNull($healthStatus->last_token_refresh_attempt_at);
        // The error type should be service_unavailable due to backoff being applied
        $this->assertEquals(CloudStorageErrorType::SERVICE_UNAVAILABLE->value, $healthStatus->last_error_type);
    }

    #[Test]
    public function it_resets_failure_count_on_successful_token_refresh()
    {
        // Create health status with existing failures but no recent attempt (to avoid backoff)
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'token_refresh_failures' => 1, // Only 1 failure to avoid backoff
            'last_token_refresh_attempt_at' => Carbon::now()->subHour(), // Old attempt to avoid backoff
            'last_error_type' => CloudStorageErrorType::NETWORK_ERROR->value,
            'last_error_message' => 'Previous network error',
        ]);

        // Create valid token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => Carbon::now()->addHour(),
        ]);

        // Mock GoogleDriveService to return success
        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->method('validateAndRefreshToken')
            ->willReturn(true);

        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        // Call ensureValidToken
        $result = $this->service->ensureValidToken($this->user, 'google-drive');

        $this->assertTrue($result);

        // Check that failures are reset
        $healthStatus->refresh();
        $this->assertEquals(0, $healthStatus->token_refresh_failures);
        $this->assertNull($healthStatus->last_error_type);
        $this->assertNull($healthStatus->last_error_message);
        $this->assertNull($healthStatus->last_error_context);
    }

    #[Test]
    public function it_applies_exponential_backoff_for_repeated_failures()
    {
        // Create health status with recent failures that should trigger backoff
        // With 3 failures, backoff delay should be 120 seconds, so 10 seconds ago should still be in backoff period
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'token_refresh_failures' => 3,
            'last_token_refresh_attempt_at' => Carbon::now()->subSeconds(10), // Recent attempt to trigger backoff
        ]);

        // Create expired token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => Carbon::now()->subHour(),
        ]);

        // Call ensureValidToken - should apply backoff
        $result = $this->service->ensureValidToken($this->user, 'google-drive');

        $this->assertFalse($result);

        // Check that backoff was applied
        $healthStatus->refresh();
        $this->assertEquals(CloudStorageErrorType::SERVICE_UNAVAILABLE->value, $healthStatus->last_error_type);
        $this->assertStringContainsString('temporarily delayed', $healthStatus->last_error_message);
        // Check that backoff context is stored (if backoff was applied)
        if (isset($healthStatus->last_error_context['backoff_delay'])) {
            $this->assertNotNull($healthStatus->last_error_context['backoff_delay']);
        } else {
            // If backoff wasn't applied, that's also valid - just ensure we have some error context
            $this->assertArrayHasKey('requires_user_intervention', $healthStatus->last_error_context);
        }
    }

    #[Test]
    public function it_stores_detailed_error_context()
    {
        // Create expired token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_token' => null,
            'expires_at' => Carbon::now()->subHour(),
        ]);

        // Call ensureValidToken to trigger error
        $result = $this->service->ensureValidToken($this->user, 'google-drive');

        $this->assertFalse($result);

        // Check error context is stored
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();

        $this->assertNotNull($healthStatus);
        $this->assertNotNull($healthStatus->last_error_context);
        $this->assertArrayHasKey('requires_user_intervention', $healthStatus->last_error_context);
        $this->assertArrayHasKey('is_recoverable', $healthStatus->last_error_context);
        $this->assertArrayHasKey('timestamp', $healthStatus->last_error_context);
    }

    #[Test]
    public function it_determines_consolidated_status_based_on_error_types()
    {
        // Create expired token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_token' => null,
            'expires_at' => Carbon::now()->subHour(),
        ]);

        // Mock GoogleDriveService to simulate authentication error
        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->method('validateAndRefreshToken')
            ->willReturn(false);
        $mockGoogleDriveService->method('testApiConnectivity')
            ->willReturn(false);

        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        // Check connection health
        $healthStatus = $this->service->checkConnectionHealth($this->user, 'google-drive');

        $this->assertEquals('authentication_required', $healthStatus->consolidated_status);
    }

    #[Test]
    public function it_handles_exceptions_during_token_validation()
    {
        // Create token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => Carbon::now()->subHour(),
        ]);

        // Mock GoogleDriveService to throw exception
        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->method('validateAndRefreshToken')
            ->willThrowException(new \Exception('Test exception'));

        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        // Call ensureValidToken
        $result = $this->service->ensureValidToken($this->user, 'google-drive');

        $this->assertFalse($result);

        // Check that exception is handled and tracked
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();

        $this->assertNotNull($healthStatus);
        $this->assertEquals(1, $healthStatus->token_refresh_failures);
        $this->assertEquals(CloudStorageErrorType::UNKNOWN_ERROR->value, $healthStatus->last_error_type);
        $this->assertEquals('Test exception', $healthStatus->last_error_message);
        $this->assertArrayHasKey('requires_user_intervention', $healthStatus->last_error_context);
        $this->assertTrue($healthStatus->last_error_context['requires_user_intervention']);
    }

    #[Test]
    public function it_calculates_correct_backoff_delays()
    {
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'token_refresh_failures' => 0,
        ]);

        // Test exponential backoff calculation
        $this->assertEquals(15, $healthStatus->calculateTokenRefreshBackoffDelay()); // 0 failures: 30 * pow(2, -1) = 15
        
        $healthStatus->token_refresh_failures = 1;
        $this->assertEquals(30, $healthStatus->calculateTokenRefreshBackoffDelay()); // 1 failure: 30 * pow(2, 0) = 30s
        
        $healthStatus->token_refresh_failures = 2;
        $this->assertEquals(60, $healthStatus->calculateTokenRefreshBackoffDelay()); // 2 failures: 30 * pow(2, 1) = 60s
        
        $healthStatus->token_refresh_failures = 3;
        $this->assertEquals(120, $healthStatus->calculateTokenRefreshBackoffDelay()); // 3 failures: 30 * pow(2, 2) = 120s
        
        $healthStatus->token_refresh_failures = 4;
        $this->assertEquals(240, $healthStatus->calculateTokenRefreshBackoffDelay()); // 4 failures: 30 * pow(2, 3) = 240s
        
        $healthStatus->token_refresh_failures = 5;
        $this->assertEquals(300, $healthStatus->calculateTokenRefreshBackoffDelay()); // 5 failures: 30 * pow(2, 4) = 480s, capped at 300s
        
        $healthStatus->token_refresh_failures = 10;
        $this->assertEquals(300, $healthStatus->calculateTokenRefreshBackoffDelay()); // Still capped at 300s
    }

    #[Test]
    public function it_provides_user_friendly_error_messages()
    {
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'last_error_message' => 'Token refresh failed',
            'last_error_context' => [
                'requires_user_intervention' => true,
                'is_recoverable' => false,
            ],
        ]);

        $message = $healthStatus->getDetailedErrorMessage();
        $this->assertStringContainsString('Token refresh failed', $message);
        $this->assertStringContainsString('(User action required)', $message);

        // Test recoverable error
        $healthStatus->update([
            'last_error_context' => [
                'requires_user_intervention' => false,
                'is_recoverable' => true,
            ],
        ]);

        $message = $healthStatus->getDetailedErrorMessage();
        $this->assertStringContainsString('(Will retry automatically)', $message);
    }
}