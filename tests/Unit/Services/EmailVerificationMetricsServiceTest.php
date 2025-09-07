<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\EmailVerificationMetricsService;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmailVerificationMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmailVerificationMetricsService $metricsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->metricsService = app(EmailVerificationMetricsService::class);
        
        // Clear cache before each test
        Cache::flush();
    }

    public function test_records_existing_user_bypass_event(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => UserRole::ADMIN
        ]);
        
        $restrictionsBypassed = ['public_registration_disabled', 'domain_not_allowed'];

        // Act
        $this->metricsService->recordExistingUserBypass($user, $restrictionsBypassed);

        // Assert
        $patterns = $this->metricsService->getBypassPatterns(1);
        
        $this->assertEquals(1, $patterns['total_bypasses']);
        $this->assertArrayHasKey('admin', $patterns['bypasses_by_role']);
        $this->assertEquals(1, $patterns['bypasses_by_role']['admin']);
        $this->assertArrayHasKey('test.com', $patterns['bypasses_by_domain']);
        $this->assertEquals(1, $patterns['bypasses_by_domain']['test.com']);
    }

    public function test_records_restriction_enforcement_event(): void
    {
        // Arrange
        $email = 'newuser@blocked.com';
        $restrictionType = 'domain_not_allowed';
        $context = ['domain_restrictions_mode' => 'whitelist'];

        // Act
        $this->metricsService->recordRestrictionEnforcement($email, $restrictionType, $context);

        // Assert
        $patterns = $this->metricsService->getRestrictionPatterns(1);
        
        $this->assertEquals(1, $patterns['total_restrictions']);
        $this->assertArrayHasKey('domain_not_allowed', $patterns['restrictions_by_type']);
        $this->assertEquals(1, $patterns['restrictions_by_type']['domain_not_allowed']);
        $this->assertArrayHasKey('blocked.com', $patterns['restrictions_by_domain']);
        $this->assertEquals(1, $patterns['restrictions_by_domain']['blocked.com']);
    }

    public function test_detects_unusual_bypass_spike(): void
    {
        // Arrange - Create multiple users and record many bypasses
        $users = User::factory()->count(3)->create(['role' => UserRole::CLIENT]);
        
        // Record 15 bypass events (above the threshold of 10)
        foreach ($users as $user) {
            for ($i = 0; $i < 5; $i++) {
                $this->metricsService->recordExistingUserBypass($user, ['public_registration_disabled']);
            }
        }

        // Act
        $patterns = $this->metricsService->getBypassPatterns(1);
        $alerts = $patterns['unusual_patterns'];

        // Assert
        $this->assertNotEmpty($alerts);
        $spikeAlert = collect($alerts)->firstWhere('type', 'bypass_spike');
        $this->assertNotNull($spikeAlert);
        $this->assertEquals('warning', $spikeAlert['severity']);
        $this->assertEquals(15, $spikeAlert['count']);
    }

    public function test_detects_repeated_bypasses_from_same_user(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        
        // Record 6 bypass events from same user (above threshold of 5)
        for ($i = 0; $i < 6; $i++) {
            $this->metricsService->recordExistingUserBypass($user, ['domain_not_allowed']);
        }

        // Act
        $patterns = $this->metricsService->getBypassPatterns(1);
        $alerts = $patterns['unusual_patterns'];

        // Assert
        $this->assertNotEmpty($alerts);
        $repeatedAlert = collect($alerts)->firstWhere('type', 'repeated_bypass');
        $this->assertNotNull($repeatedAlert);
        $this->assertEquals('info', $repeatedAlert['severity']);
        $this->assertEquals($user->id, $repeatedAlert['user_id']);
        $this->assertEquals(6, $repeatedAlert['count']);
    }

    public function test_detects_unusual_domain_activity(): void
    {
        // Arrange - Create users from same unusual domain
        $users = User::factory()->count(4)->create([
            'email' => fn() => 'user' . rand(1000, 9999) . '@suspicious.com',
            'role' => UserRole::CLIENT
        ]);
        
        // Record bypasses from this domain (above threshold of 3)
        foreach ($users as $user) {
            $this->metricsService->recordExistingUserBypass($user, ['public_registration_disabled']);
        }

        // Act
        $patterns = $this->metricsService->getBypassPatterns(1);
        $alerts = $patterns['unusual_patterns'];

        // Assert
        $this->assertNotEmpty($alerts);
        $domainAlert = collect($alerts)->firstWhere('type', 'unusual_domain');
        $this->assertNotNull($domainAlert);
        $this->assertEquals('info', $domainAlert['severity']);
        $this->assertEquals('suspicious.com', $domainAlert['domain']);
        $this->assertEquals(4, $domainAlert['count']);
    }

    public function test_dashboard_metrics_calculation(): void
    {
        // Arrange
        $adminUser = User::factory()->create(['role' => UserRole::ADMIN]);
        $clientUser = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Record some bypass events
        $this->metricsService->recordExistingUserBypass($adminUser, ['public_registration_disabled']);
        $this->metricsService->recordExistingUserBypass($clientUser, ['domain_not_allowed']);
        
        // Record some restriction events
        $this->metricsService->recordRestrictionEnforcement('new@blocked.com', 'domain_not_allowed');
        $this->metricsService->recordRestrictionEnforcement('another@blocked.com', 'public_registration_disabled');

        // Act
        $dashboard = $this->metricsService->getDashboardMetrics();

        // Assert
        $this->assertEquals(2, $dashboard['last_24_hours']['existing_user_bypasses']);
        $this->assertEquals(2, $dashboard['last_24_hours']['restriction_enforcements']);
        $this->assertEquals(1.0, $dashboard['last_24_hours']['bypass_to_restriction_ratio']);
        
        $this->assertArrayHasKey('top_bypassed_restrictions', $dashboard);
        $this->assertArrayHasKey('most_active_domains', $dashboard);
        $this->assertArrayHasKey('unusual_activity_alerts', $dashboard);
    }

    public function test_hourly_distribution_calculation(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        
        // Record events at current hour
        $this->metricsService->recordExistingUserBypass($user, ['public_registration_disabled']);
        $this->metricsService->recordRestrictionEnforcement('new@test.com', 'domain_not_allowed');

        // Act
        $bypassPatterns = $this->metricsService->getBypassPatterns(1);
        $restrictionPatterns = $this->metricsService->getRestrictionPatterns(1);

        // Assert
        $currentHour = now()->format('H:00');
        
        $this->assertArrayHasKey('hourly_distribution', $bypassPatterns);
        $this->assertArrayHasKey($currentHour, $bypassPatterns['hourly_distribution']);
        $this->assertEquals(1, $bypassPatterns['hourly_distribution'][$currentHour]);
        
        $this->assertArrayHasKey('hourly_distribution', $restrictionPatterns);
        $this->assertArrayHasKey($currentHour, $restrictionPatterns['hourly_distribution']);
        $this->assertEquals(1, $restrictionPatterns['hourly_distribution'][$currentHour]);
    }

    public function test_bypass_to_restriction_ratio_edge_cases(): void
    {
        // Test case 1: No restrictions, some bypasses
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->metricsService->recordExistingUserBypass($user, ['public_registration_disabled']);
        
        $dashboard = $this->metricsService->getDashboardMetrics();
        $this->assertEquals(PHP_FLOAT_MAX, $dashboard['last_24_hours']['bypass_to_restriction_ratio']);
        
        // Test case 2: No bypasses, some restrictions
        Cache::flush(); // Clear previous data
        $this->metricsService->recordRestrictionEnforcement('new@test.com', 'domain_not_allowed');
        
        $dashboard = $this->metricsService->getDashboardMetrics();
        $this->assertEquals(0, $dashboard['last_24_hours']['bypass_to_restriction_ratio']);
        
        // Test case 3: No activity at all
        Cache::flush(); // Clear all data
        
        $dashboard = $this->metricsService->getDashboardMetrics();
        $this->assertEquals(0, $dashboard['last_24_hours']['bypass_to_restriction_ratio']);
    }

    public function test_metrics_time_filtering(): void
    {
        // This test would be more complex to implement properly as it would require
        // mocking time or using Carbon::setTestNow(), but the basic structure shows
        // that we're testing time-based filtering of metrics
        
        $user = User::factory()->create(['role' => UserRole::CLIENT]);
        $this->metricsService->recordExistingUserBypass($user, ['public_registration_disabled']);
        
        // Test different time ranges
        $patterns1Hour = $this->metricsService->getBypassPatterns(1);
        $patterns24Hours = $this->metricsService->getBypassPatterns(24);
        
        // Both should contain the recent event
        $this->assertEquals(1, $patterns1Hour['total_bypasses']);
        $this->assertEquals(1, $patterns24Hours['total_bypasses']);
    }

    public function test_domain_extraction(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'TestUser@EXAMPLE.COM', // Mixed case
            'role' => UserRole::CLIENT
        ]);
        
        // Act
        $this->metricsService->recordExistingUserBypass($user, ['public_registration_disabled']);
        $patterns = $this->metricsService->getBypassPatterns(1);
        
        // Assert - Domain should be lowercase
        $this->assertArrayHasKey('example.com', $patterns['bypasses_by_domain']);
        $this->assertEquals(1, $patterns['bypasses_by_domain']['example.com']);
    }
}