<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\TokenRefreshConfig;
use App\Services\TokenRefreshConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TokenRefreshConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Load the configuration file
        $config = require __DIR__ . '/../../../config/token-refresh.php';
        
        // Override testing environment to not disable all features for unit tests
        $config['environments']['testing'] = [
            'features' => [
                'proactive_refresh' => true,
                'live_validation' => false,
                'automatic_recovery' => true,
                'background_maintenance' => true,
                'health_monitoring' => true,
                'enhanced_dashboard' => true,
                'enhanced_logging' => true,
            ],
            'notifications' => [
                'enabled' => true,
            ],
        ];
        
        $this->app['config']->set('token-refresh', $config);

        // Set up basic configuration
        Config::set('token-refresh.features.proactive_refresh', true);
        Config::set('token-refresh.features.live_validation', false);
        Config::set('token-refresh.timing.proactive_refresh_minutes', 15);
        Config::set('token-refresh.timing.max_retry_attempts', 5);
        Config::set('token-refresh.notifications.enabled', true);
        Config::set('token-refresh.notifications.throttle_hours', 24);
        Config::set('token-refresh.admin_interface.allow_runtime_changes', true);
        Config::set('token-refresh.admin_interface.modifiable_settings', [
            'features.proactive_refresh',
            'timing.proactive_refresh_minutes',
        ]);
        Config::set('token-refresh.admin_interface.require_confirmation', []);
    }

    public function test_show_action_displays_configuration_in_table_format()
    {
        $this->artisan('token-refresh:config show')
            ->expectsOutput('Token Refresh Configuration')
            ->expectsOutput('Environment: testing')
            ->assertExitCode(0);
    }

    public function test_show_action_displays_configuration_in_json_format()
    {
        $this->artisan('token-refresh:config show --format=json')
            ->expectsOutputToContain('"environment"')
            ->expectsOutputToContain('"features"')
            ->expectsOutputToContain('"timing"')
            ->assertExitCode(0);
    }

    public function test_set_action_updates_configuration()
    {
        $this->artisan('token-refresh:config set --key=timing.proactive_refresh_minutes --value=10')
            ->expectsOutput("Configuration 'timing.proactive_refresh_minutes' updated successfully to: 10")
            ->assertExitCode(0);

        $this->assertEquals(10, config('token-refresh.timing.proactive_refresh_minutes'));
    }

    public function test_set_action_fails_without_required_options()
    {
        $this->artisan('token-refresh:config set')
            ->expectsOutput('Both --key and --value options are required for set action')
            ->assertExitCode(1);
    }

    public function test_set_action_fails_when_runtime_changes_disabled()
    {
        Config::set('token-refresh.admin_interface.allow_runtime_changes', false);

        $this->artisan('token-refresh:config set --key=timing.proactive_refresh_minutes --value=10')
            ->expectsOutput('Runtime configuration changes are disabled')
            ->assertExitCode(1);
    }

    public function test_set_action_prompts_for_confirmation_when_required()
    {
        Config::set('token-refresh.admin_interface.require_confirmation', [
            'timing.proactive_refresh_minutes',
        ]);

        $this->artisan('token-refresh:config set --key=timing.proactive_refresh_minutes --value=10')
            ->expectsConfirmation("Changing 'timing.proactive_refresh_minutes' may affect system behavior. Continue?", 'yes')
            ->expectsOutput("Configuration 'timing.proactive_refresh_minutes' updated successfully to: 10")
            ->assertExitCode(0);
    }

    public function test_set_action_cancels_when_confirmation_declined()
    {
        Config::set('token-refresh.admin_interface.require_confirmation', [
            'timing.proactive_refresh_minutes',
        ]);

        $this->artisan('token-refresh:config set --key=timing.proactive_refresh_minutes --value=10')
            ->expectsConfirmation("Changing 'timing.proactive_refresh_minutes' may affect system behavior. Continue?", 'no')
            ->expectsOutput('Operation cancelled')
            ->assertExitCode(0);

        // Configuration should not have changed
        $this->assertEquals(15, config('token-refresh.timing.proactive_refresh_minutes'));
    }

    public function test_toggle_action_enables_feature()
    {
        Config::set('token-refresh.features.live_validation', false);

        $this->artisan('token-refresh:config toggle --feature=live_validation --enabled=true')
            ->expectsOutput("Feature 'live_validation' enabled successfully")
            ->assertExitCode(0);

        $this->assertTrue(config('token-refresh.features.live_validation'));
    }

    public function test_toggle_action_disables_feature()
    {
        Config::set('token-refresh.features.proactive_refresh', true);

        $this->artisan('token-refresh:config toggle --feature=proactive_refresh --enabled=false')
            ->expectsOutput("Feature 'proactive_refresh' disabled successfully")
            ->assertExitCode(0);

        $this->assertFalse(config('token-refresh.features.proactive_refresh'));
    }

    public function test_toggle_action_fails_without_required_options()
    {
        $this->artisan('token-refresh:config toggle')
            ->expectsOutput('Both --feature and --enabled options are required for toggle action')
            ->assertExitCode(1);
    }

    public function test_toggle_action_prompts_for_confirmation_when_required()
    {
        Config::set('token-refresh.admin_interface.require_confirmation', [
            'features.proactive_refresh',
        ]);

        $this->artisan('token-refresh:config toggle --feature=proactive_refresh --enabled=false')
            ->expectsConfirmation("Toggling 'proactive_refresh' may significantly affect system behavior. Continue?", 'yes')
            ->expectsOutput("Feature 'proactive_refresh' disabled successfully")
            ->assertExitCode(0);
    }

    public function test_validate_action_passes_for_valid_configuration()
    {
        $this->artisan('token-refresh:config validate')
            ->expectsOutput('✓ Configuration is valid')
            ->assertExitCode(0);
    }

    public function test_validate_action_fails_for_invalid_configuration()
    {
        Config::set('token-refresh.timing.proactive_refresh_minutes', 0); // Invalid
        Config::set('token-refresh.timing.max_retry_attempts', 15); // Invalid

        $this->artisan('token-refresh:config validate')
            ->expectsOutput('Configuration validation failed:')
            ->expectsOutput('• Proactive refresh minutes must be at least 1')
            ->expectsOutput('• Max retry attempts must be between 1 and 10')
            ->assertExitCode(1);
    }

    public function test_clear_cache_action_succeeds()
    {
        $this->artisan('token-refresh:config clear-cache')
            ->expectsOutput('Configuration cache cleared successfully')
            ->assertExitCode(0);
    }

    public function test_unknown_action_returns_error()
    {
        $this->artisan('token-refresh:config unknown')
            ->expectsOutput('Unknown action: unknown')
            ->assertExitCode(1);
    }

    public function test_value_conversion_works_for_boolean_features()
    {
        // Test various boolean representations
        $booleanValues = [
            'true' => true,
            'false' => false,
            '1' => true,
            '0' => false,
            'yes' => true,
            'no' => false,
        ];

        foreach ($booleanValues as $input => $expected) {
            Config::set('token-refresh.features.proactive_refresh', !$expected); // Set opposite

            $this->artisan("token-refresh:config toggle --feature=proactive_refresh --enabled={$input}")
                ->assertExitCode(0);

            $this->assertEquals($expected, config('token-refresh.features.proactive_refresh'));
        }
    }

    public function test_value_conversion_works_for_integer_settings()
    {
        $this->artisan('token-refresh:config set --key=timing.proactive_refresh_minutes --value=25')
            ->assertExitCode(0);

        $this->assertSame(25, config('token-refresh.timing.proactive_refresh_minutes'));
    }

    public function test_command_handles_service_exceptions_gracefully()
    {
        // Mock the service to throw an exception
        $this->mock(TokenRefreshConfigService::class, function ($mock) {
            $mock->shouldReceive('getAdminConfig')
                ->with('allow_runtime_changes')
                ->andReturn(true);
            
            $mock->shouldReceive('getAdminConfig')
                ->with('modifiable_settings')
                ->andReturn(['features.proactive_refresh']);
            
            $mock->shouldReceive('getAdminConfig')
                ->with('require_confirmation')
                ->andReturn([]);
            
            $mock->shouldReceive('updateConfig')
                ->andThrow(new \Exception('Service error'));
        });

        $this->artisan('token-refresh:config set --key=features.proactive_refresh --value=false')
            ->expectsOutput("Failed to update configuration 'features.proactive_refresh'")
            ->assertExitCode(1);
    }

    public function test_command_displays_feature_descriptions_in_show_output()
    {
        $this->artisan('token-refresh:config show')
            ->expectsOutput('Feature Flags:')
            ->expectsOutput('Proactive Refresh')
            ->expectsOutput('Live Validation')
            ->assertExitCode(0);
    }

    public function test_command_displays_timing_descriptions_in_show_output()
    {
        $this->artisan('token-refresh:config show')
            ->expectsOutput('Timing Configuration:')
            ->expectsOutput('Proactive Refresh Minutes')
            ->expectsOutput('Max Retry Attempts')
            ->assertExitCode(0);
    }

    public function test_command_displays_notification_descriptions_in_show_output()
    {
        $this->artisan('token-refresh:config show')
            ->expectsOutput('Notification Configuration:')
            ->expectsOutput('Enabled')
            ->expectsOutput('Throttle Hours')
            ->assertExitCode(0);
    }

    public function test_command_displays_rate_limiting_descriptions_in_show_output()
    {
        $this->artisan('token-refresh:config show')
            ->expectsOutput('Rate Limiting Configuration:')
            ->assertExitCode(0);
    }
}