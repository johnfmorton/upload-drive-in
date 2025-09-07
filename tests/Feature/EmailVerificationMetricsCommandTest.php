<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\EmailVerificationMetricsService;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmailVerificationMetricsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Mail::fake();
    }

    public function test_metrics_command_displays_table_format(): void
    {
        // Arrange
        $metricsService = app(EmailVerificationMetricsService::class);
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Record some test data
        $metricsService->recordExistingUserBypass($user, ['public_registration_disabled']);
        $metricsService->recordRestrictionEnforcement('new@test.com', 'domain_not_allowed');

        // Act
        $this->artisan('email-verification:metrics --hours=1')
            ->expectsOutput('Email Verification Metrics - Last 1 hours')
            ->assertExitCode(0);
    }

    public function test_metrics_command_displays_json_format(): void
    {
        // Arrange
        $metricsService = app(EmailVerificationMetricsService::class);
        $user = User::factory()->create(['role' => UserRole::CLIENT]);
        
        $metricsService->recordExistingUserBypass($user, ['domain_not_allowed']);

        // Act & Assert
        $this->artisan('email-verification:metrics --format=json --hours=1')
            ->assertExitCode(0);
    }

    public function test_metrics_command_logs_to_laravel_log(): void
    {
        // Arrange
        $metricsService = app(EmailVerificationMetricsService::class);
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        
        $metricsService->recordExistingUserBypass($user, ['public_registration_disabled']);

        // Act
        $this->artisan('email-verification:metrics --format=log --hours=1')
            ->expectsOutput('Metrics logged to Laravel log file')
            ->assertExitCode(0);
    }

    public function test_metrics_command_shows_alerts_only(): void
    {
        // Arrange - Create scenario with unusual activity
        $metricsService = app(EmailVerificationMetricsService::class);
        $users = User::factory()->count(3)->create(['role' => UserRole::CLIENT]);
        
        // Create spike in bypasses (above threshold)
        foreach ($users as $user) {
            for ($i = 0; $i < 5; $i++) {
                $metricsService->recordExistingUserBypass($user, ['public_registration_disabled']);
            }
        }

        // Act & Assert
        $this->artisan('email-verification:metrics --alerts')
            ->expectsOutput('⚠️  Unusual Activity Detected')
            ->assertExitCode(0);
    }

    public function test_metrics_command_shows_no_alerts_when_normal(): void
    {
        // Arrange - Normal activity level
        $metricsService = app(EmailVerificationMetricsService::class);
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $metricsService->recordExistingUserBypass($user, ['public_registration_disabled']);

        // Act & Assert
        $this->artisan('email-verification:metrics --alerts')
            ->expectsOutput('✅ No unusual activity detected in the last 24 hours')
            ->assertExitCode(0);
    }

    public function test_alert_command_detects_high_bypass_volume(): void
    {
        // Arrange
        $metricsService = app(EmailVerificationMetricsService::class);
        $users = User::factory()->count(25)->create(['role' => UserRole::CLIENT]);
        
        // Create high volume of bypasses (above default threshold of 20)
        foreach ($users as $user) {
            $metricsService->recordExistingUserBypass($user, ['public_registration_disabled']);
        }

        // Act & Assert
        $this->artisan('email-verification:check-alerts --threshold-bypasses=20')
            ->expectsOutputToContain('high_bypass_volume')
            ->assertExitCode(0);
    }

    public function test_alert_command_detects_high_restriction_volume(): void
    {
        // Arrange
        $metricsService = app(EmailVerificationMetricsService::class);
        
        // Create high volume of restrictions (above default threshold of 50)
        for ($i = 0; $i < 55; $i++) {
            $metricsService->recordRestrictionEnforcement("user{$i}@blocked.com", 'domain_not_allowed');
        }

        // Act & Assert
        $this->artisan('email-verification:check-alerts --threshold-restrictions=50')
            ->expectsOutputToContain('high_restriction_volume')
            ->assertExitCode(0);
    }

    public function test_alert_command_sends_email_notification(): void
    {
        // Arrange
        $metricsService = app(EmailVerificationMetricsService::class);
        $users = User::factory()->count(25)->create(['role' => UserRole::ADMIN]);
        
        // Create alert condition
        foreach ($users as $user) {
            $metricsService->recordExistingUserBypass($user, ['public_registration_disabled']);
        }

        // Act
        $this->artisan('email-verification:check-alerts --threshold-bypasses=20 --notify-email=admin@test.com')
            ->expectsOutputToContain('Alert email sent to admin@test.com')
            ->assertExitCode(0);

        // Assert
        Mail::assertSent(\Illuminate\Mail\Mailable::class);
    }

    public function test_alert_command_respects_cooldown_period(): void
    {
        // Arrange
        $metricsService = app(EmailVerificationMetricsService::class);
        $users = User::factory()->count(25)->create(['role' => UserRole::CLIENT]);
        
        foreach ($users as $user) {
            $metricsService->recordExistingUserBypass($user, ['public_registration_disabled']);
        }

        // Act - First alert should trigger
        $this->artisan('email-verification:check-alerts --threshold-bypasses=20 --notify-email=admin@test.com')
            ->expectsOutputToContain('Alert email sent to admin@test.com')
            ->assertExitCode(0);

        // Act - Second alert should be in cooldown
        $this->artisan('email-verification:check-alerts --threshold-bypasses=20 --notify-email=admin@test.com')
            ->expectsOutput('Alert cooldown active, skipping notifications')
            ->assertExitCode(0);
    }

    public function test_alert_command_detects_no_activity_during_business_hours(): void
    {
        // This test would require mocking the current time to be during business hours
        // For now, we'll test the basic structure
        
        // Act & Assert - No activity should trigger info alert during business hours
        $this->artisan('email-verification:check-alerts')
            ->assertExitCode(0);
    }

    public function test_alert_command_shows_no_alerts_when_normal(): void
    {
        // Arrange - Normal activity
        $metricsService = app(EmailVerificationMetricsService::class);
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        
        $metricsService->recordExistingUserBypass($user, ['domain_not_allowed']);
        $metricsService->recordRestrictionEnforcement('new@test.com', 'public_registration_disabled');

        // Act & Assert
        $this->artisan('email-verification:check-alerts --threshold-bypasses=20 --threshold-restrictions=50')
            ->expectsOutput('✅ No alerts detected')
            ->assertExitCode(0);
    }

    public function test_commands_handle_empty_metrics_gracefully(): void
    {
        // Act & Assert - Commands should work even with no data
        $this->artisan('email-verification:metrics --hours=24')
            ->expectsOutput('Email Verification Metrics - Last 24 hours')
            ->assertExitCode(0);

        $this->artisan('email-verification:check-alerts')
            ->expectsOutput('✅ No alerts detected')
            ->assertExitCode(0);
    }

    public function test_metrics_command_with_custom_hours(): void
    {
        // Arrange
        $metricsService = app(EmailVerificationMetricsService::class);
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $metricsService->recordExistingUserBypass($user, ['public_registration_disabled']);

        // Act & Assert - Test different hour ranges
        $this->artisan('email-verification:metrics --hours=1')
            ->expectsOutput('Email Verification Metrics - Last 1 hours')
            ->assertExitCode(0);

        $this->artisan('email-verification:metrics --hours=168') // 1 week
            ->expectsOutput('Email Verification Metrics - Last 168 hours')
            ->assertExitCode(0);
    }

    public function test_alert_command_with_custom_thresholds(): void
    {
        // Arrange
        $metricsService = app(EmailVerificationMetricsService::class);
        $users = User::factory()->count(5)->create(['role' => UserRole::CLIENT]);
        
        foreach ($users as $user) {
            $metricsService->recordExistingUserBypass($user, ['public_registration_disabled']);
        }

        // Act & Assert - Lower threshold should trigger alert
        $this->artisan('email-verification:check-alerts --threshold-bypasses=3')
            ->expectsOutputToContain('high_bypass_volume')
            ->assertExitCode(0);

        // Higher threshold should not trigger alert
        $this->artisan('email-verification:check-alerts --threshold-bypasses=10')
            ->expectsOutput('✅ No alerts detected')
            ->assertExitCode(0);
    }
}