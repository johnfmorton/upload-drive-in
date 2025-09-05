<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Models\CloudStorageHealthStatus;
use App\Services\RealTimeHealthValidator;
use App\Services\TokenStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CloudStorageStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com'
        ]);
        
        $this->employeeUser = User::factory()->create([
            'role' => 'employee',
            'email' => 'employee@example.com'
        ]);
    }

    /** @test */
    public function admin_dashboard_shows_accurate_connection_status()
    {
        // Create a healthy token
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'expires_at' => now()->addHours(2),
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
        ]);

        // Create corresponding health status
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->adminUser->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'last_successful_operation_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Cloud Storage Status')
            ->assertSee('Google Drive');
    }

    /** @test */
    public function test_connection_button_uses_real_time_validation()
    {
        // Create a token that appears healthy but might have issues
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'expires_at' => now()->addHours(2),
        ]);

        // Mock the RealTimeHealthValidator to return specific results
        $this->mock(RealTimeHealthValidator::class, function ($mock) {
            $mock->shouldReceive('validateConnectionHealth')
                ->once()
                ->andReturn(new \App\Services\HealthStatus(
                    isHealthy: true,
                    status: 'healthy',
                    validationDetails: ['validation_time_ms' => 150],
                    validatedAt: now(),
                    cacheTtlSeconds: 30
                ));
        });

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.cloud-storage.test'), [
                'provider' => 'google-drive'
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'test_type' => 'real_time_validation',
            ])
            ->assertJsonStructure([
                'message',
                'status',
                'status_localized',
                'validation_details',
                'validated_at',
                'token_status',
                'cache_ttl_seconds'
            ]);
    }

    /** @test */
    public function dashboard_status_and_test_button_return_consistent_results()
    {
        // Create a token with specific conditions
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'expires_at' => now()->addMinutes(10), // Expiring soon
            'refresh_failure_count' => 1,
        ]);

        // Clear any cached results to ensure fresh validation
        Cache::flush();

        // Get dashboard status
        $dashboardResponse = $this->actingAs($this->adminUser)
            ->getJson(route('admin.dashboard.cloud-storage.status'));

        // Test connection using the same validation logic
        $testResponse = $this->actingAs($this->adminUser)
            ->postJson(route('admin.cloud-storage.test'), [
                'provider' => 'google-drive'
            ]);

        $dashboardResponse->assertOk();
        $testResponse->assertOk();

        // Both should use real-time validation and return consistent status
        $dashboardData = $dashboardResponse->json('data')[0] ?? $dashboardResponse->json('providers')[0];
        $testData = $testResponse->json();

        // Status should be consistent between dashboard and test
        $this->assertEquals($testData['status'], $dashboardData['token_status']['status'] ?? 'unknown');
        
        // Both should indicate they used enhanced validation
        $this->assertEquals('enhanced_with_token_details', $dashboardResponse->json('status_type'));
        $this->assertEquals('real_time_validation', $testResponse->json('test_type'));
    }

    /** @test */
    public function token_status_displays_comprehensive_information()
    {
        $issuedAt = now()->subDays(5);
        $expiresAt = now()->addHours(3);
        
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'created_at' => $issuedAt,
            'expires_at' => $expiresAt,
            'last_successful_refresh_at' => now()->subHours(1),
            'refresh_failure_count' => 0,
            'scopes' => ['https://www.googleapis.com/auth/drive.file'],
        ]);

        $tokenStatusService = app(TokenStatusService::class);
        $tokenStatus = $tokenStatusService->getTokenStatus($this->adminUser, 'google-drive');

        $this->assertNotNull($tokenStatus);
        $this->assertTrue($tokenStatus['exists']);
        $this->assertEquals('healthy', $tokenStatus['status']);
        $this->assertEquals('green', $tokenStatus['health_indicator']);
        
        // Check comprehensive information is present
        $this->assertArrayHasKey('issued_at_human', $tokenStatus);
        $this->assertArrayHasKey('expires_at_human', $tokenStatus);
        $this->assertArrayHasKey('expires_in_human', $tokenStatus);
        $this->assertArrayHasKey('next_renewal_at_human', $tokenStatus);
        $this->assertArrayHasKey('last_successful_refresh_human', $tokenStatus);
        
        // Verify time calculations
        $this->assertFalse($tokenStatus['is_expired']);
        $this->assertFalse($tokenStatus['is_expiring_soon']); // 3 hours > 15 minutes
        $this->assertTrue($tokenStatus['can_be_refreshed']);
    }

    /** @test */
    public function token_status_handles_expiring_soon_scenario()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'expires_at' => now()->addMinutes(10), // Expiring in 10 minutes
            'refresh_failure_count' => 0,
        ]);

        $tokenStatusService = app(TokenStatusService::class);
        $tokenStatus = $tokenStatusService->getTokenStatus($this->adminUser, 'google-drive');

        $this->assertEquals('expiring_soon', $tokenStatus['status']);
        $this->assertEquals('yellow', $tokenStatus['health_indicator']);
        $this->assertTrue($tokenStatus['is_expiring_soon']);
        $this->assertFalse($tokenStatus['is_expired']);
        $this->assertStringContains('automatically renewed soon', $tokenStatus['message']);
    }

    /** @test */
    public function token_status_handles_expired_but_refreshable_scenario()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'expires_at' => now()->subMinutes(5), // Expired 5 minutes ago
            'refresh_failure_count' => 2, // Some failures but still under limit
            'requires_user_intervention' => false,
        ]);

        $tokenStatusService = app(TokenStatusService::class);
        $tokenStatus = $tokenStatusService->getTokenStatus($this->adminUser, 'google-drive');

        $this->assertEquals('expired_refreshable', $tokenStatus['status']);
        $this->assertEquals('yellow', $tokenStatus['health_indicator']);
        $this->assertTrue($tokenStatus['is_expired']);
        $this->assertTrue($tokenStatus['can_be_refreshed']);
        $this->assertStringContains('automatically refreshed', $tokenStatus['message']);
    }

    /** @test */
    public function token_status_handles_requires_intervention_scenario()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'expires_at' => now()->subHours(1),
            'refresh_failure_count' => 5, // Max failures reached
            'requires_user_intervention' => true,
        ]);

        $tokenStatusService = app(TokenStatusService::class);
        $tokenStatus = $tokenStatusService->getTokenStatus($this->adminUser, 'google-drive');

        $this->assertEquals('requires_intervention', $tokenStatus['status']);
        $this->assertEquals('red', $tokenStatus['health_indicator']);
        $this->assertTrue($tokenStatus['requires_user_intervention']);
        $this->assertFalse($tokenStatus['can_be_refreshed']);
        $this->assertStringContains('manual reconnection', $tokenStatus['message']);
    }

    /** @test */
    public function employee_dashboard_shows_consistent_status_with_admin()
    {
        // Create identical token conditions for both users
        $tokenData = [
            'expires_at' => now()->addHours(2),
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
        ];

        $adminToken = GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            ...$tokenData
        ]);

        $employeeToken = GoogleDriveToken::factory()->create([
            'user_id' => $this->employeeUser->id,
            ...$tokenData
        ]);

        // Clear cache to ensure fresh validation
        Cache::flush();

        // Test admin connection
        $adminTestResponse = $this->actingAs($this->adminUser)
            ->postJson(route('admin.cloud-storage.test'), [
                'provider' => 'google-drive'
            ]);

        // Test employee connection
        $employeeTestResponse = $this->actingAs($this->employeeUser)
            ->postJson(route('employee.cloud-storage.test', ['username' => $this->employeeUser->username]), [
                'provider' => 'google-drive'
            ]);

        $adminTestResponse->assertOk();
        $employeeTestResponse->assertOk();

        // Both should return the same validation logic and structure
        $adminData = $adminTestResponse->json();
        $employeeData = $employeeTestResponse->json();

        $this->assertEquals($adminData['test_type'], $employeeData['test_type']);
        $this->assertEquals('real_time_validation', $adminData['test_type']);
        
        // Both should have token status information
        $this->assertArrayHasKey('token_status', $adminData);
        $this->assertArrayHasKey('token_status', $employeeData);
    }

    /** @test */
    public function real_time_validation_bypasses_stale_cache()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'expires_at' => now()->addHours(2),
        ]);

        // Put stale data in cache
        $staleHealthStatus = new \App\Services\HealthStatus(
            isHealthy: false,
            status: 'connection_issues',
            errorMessage: 'Stale cached error',
            validatedAt: now()->subMinutes(10)
        );
        
        Cache::put("real_time_health_{$this->adminUser->id}_google-drive", $staleHealthStatus->toArray(), 60);

        // Mock the validator to return fresh data (bypassing cache)
        $this->mock(RealTimeHealthValidator::class, function ($mock) {
            $mock->shouldReceive('validateConnectionHealth')
                ->once()
                ->andReturn(new \App\Services\HealthStatus(
                    isHealthy: true,
                    status: 'healthy',
                    validationDetails: ['validation_time_ms' => 200],
                    validatedAt: now()
                ));
        });

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.cloud-storage.test'), [
                'provider' => 'google-drive'
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'healthy'
            ])
            ->assertJsonMissing([
                'error_message' => 'Stale cached error'
            ]);
    }

    /** @test */
    public function dashboard_displays_loading_states_during_validation()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'expires_at' => now()->addHours(2),
        ]);

        // Visit the dashboard page
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Testing...')  // Loading state text
            ->assertSee('isTesting')   // JavaScript loading state variable
            ->assertSee('disabled:opacity-50'); // Disabled button styling
    }
}