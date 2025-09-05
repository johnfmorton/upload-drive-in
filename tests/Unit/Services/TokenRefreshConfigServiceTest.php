<?php

namespace Tests\Unit\Services;

use App\Services\TokenRefreshConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TokenRefreshConfigServiceTest extends TestCase
{
    use RefreshDatabase;

    private TokenRefreshConfigService $configService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load the configuration file
        $config = require __DIR__ . '/../../../config/token-refresh.php';
        
        // Override testing environment to not disable all features for unit tests
        $config['environments']['testing'] = [
            'features' => [
                'proactive_refresh' => true,
                'live_validation' => true,
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
        
        $this->configService = new TokenRefreshConfigService();
        
        // Clear cache before each test
        Cache::flush();
    }

    public function test_is_feature_enabled_returns_correct_value()
    {
        // Set up configuration - override environment config
        Config::set('token-refresh.features.proactive_refresh', true);
        Config::set('token-refresh.features.live_validation', false);
        Config::set('token-refresh.environments.testing.features.proactive_refresh', true);
        Config::set('token-refresh.environments.testing.features.live_validation', false);

        $this->assertTrue($this->configService->isFeatureEnabled('proactive_refresh'));
        $this->assertFalse($this->configService->isFeatureEnabled('live_validation'));
    }

    public function test_is_feature_enabled_uses_environment_override()
    {
        // Set up base configuration
        Config::set('token-refresh.features.proactive_refresh', true);
        
        // Set up environment-specific override
        Config::set('token-refresh.environments.testing.features.proactive_refresh', false);
        
        // Mock environment
        app()->detectEnvironment(function () {
            return 'testing';
        });

        $this->assertFalse($this->configService->isFeatureEnabled('proactive_refresh'));
    }

    public function test_get_timing_config_returns_correct_values()
    {
        Config::set('token-refresh.timing.proactive_refresh_minutes', 15);
        Config::set('token-refresh.timing.max_retry_attempts', 5);

        $this->assertEquals(15, $this->configService->getTimingConfig('proactive_refresh_minutes'));
        $this->assertEquals(5, $this->configService->getTimingConfig('max_retry_attempts'));
    }

    public function test_get_timing_config_uses_environment_override()
    {
        // Set up base configuration
        Config::set('token-refresh.timing.proactive_refresh_minutes', 15);
        
        // Set up environment-specific override
        Config::set('token-refresh.environments.local.timing.proactive_refresh_minutes', 5);
        
        // Mock environment
        app()->detectEnvironment(function () {
            return 'local';
        });

        $this->assertEquals(5, $this->configService->getTimingConfig('proactive_refresh_minutes'));
    }

    public function test_get_notification_config_returns_correct_values()
    {
        Config::set('token-refresh.notifications.enabled', true);
        Config::set('token-refresh.notifications.throttle_hours', 24);

        $this->assertTrue($this->configService->getNotificationConfig('enabled'));
        $this->assertEquals(24, $this->configService->getNotificationConfig('throttle_hours'));
    }

    public function test_get_notification_config_returns_all_when_no_key()
    {
        $notifications = [
            'enabled' => true,
            'throttle_hours' => 24,
            'escalate_to_admin' => true,
        ];
        
        Config::set('token-refresh.notifications', $notifications);

        $result = $this->configService->getNotificationConfig();
        
        $this->assertEquals($notifications, $result);
    }

    public function test_convenience_methods_return_correct_values()
    {
        Config::set('token-refresh.features.proactive_refresh', true);
        Config::set('token-refresh.features.live_validation', false);
        Config::set('token-refresh.timing.proactive_refresh_minutes', 15);
        Config::set('token-refresh.notifications.enabled', true);
        
        // Override environment config
        Config::set('token-refresh.environments.testing.features.proactive_refresh', true);
        Config::set('token-refresh.environments.testing.features.live_validation', false);

        $this->assertTrue($this->configService->isProactiveRefreshEnabled());
        $this->assertFalse($this->configService->isLiveValidationEnabled());
        $this->assertEquals(15, $this->configService->getProactiveRefreshMinutes());
        $this->assertTrue($this->configService->areNotificationsEnabled());
    }

    public function test_update_config_succeeds_when_allowed()
    {
        Config::set('token-refresh.admin_interface.allow_runtime_changes', true);
        Config::set('token-refresh.admin_interface.modifiable_settings', ['features.proactive_refresh']);

        $result = $this->configService->updateConfig('features.proactive_refresh', false);

        $this->assertTrue($result);
        $this->assertFalse(config('token-refresh.features.proactive_refresh'));
    }

    public function test_update_config_fails_when_not_allowed()
    {
        Config::set('token-refresh.admin_interface.allow_runtime_changes', false);

        $result = $this->configService->updateConfig('features.proactive_refresh', false);

        $this->assertFalse($result);
    }

    public function test_update_config_fails_for_non_modifiable_setting()
    {
        Config::set('token-refresh.admin_interface.allow_runtime_changes', true);
        Config::set('token-refresh.admin_interface.modifiable_settings', ['features.proactive_refresh']);

        $result = $this->configService->updateConfig('features.live_validation', false);

        $this->assertFalse($result);
    }

    public function test_clear_config_cache_clears_specific_cache()
    {
        // Set up cache
        Cache::put('token_refresh_config:features:proactive_refresh', true, 300);
        Cache::put('token_refresh_config:timing:max_retry_attempts', 5, 300);

        $this->assertTrue(Cache::has('token_refresh_config:features:proactive_refresh'));
        $this->assertTrue(Cache::has('token_refresh_config:timing:max_retry_attempts'));

        $this->configService->clearConfigCache('features.proactive_refresh');

        $this->assertFalse(Cache::has('token_refresh_config:features:proactive_refresh'));
        $this->assertTrue(Cache::has('token_refresh_config:timing:max_retry_attempts'));
    }

    public function test_get_all_feature_flags_returns_merged_config()
    {
        Config::set('token-refresh.features', [
            'proactive_refresh' => true,
            'live_validation' => false,
        ]);
        
        Config::set('token-refresh.environments.testing.features', [
            'live_validation' => true,
        ]);
        
        // Mock environment
        app()->detectEnvironment(function () {
            return 'testing';
        });

        $flags = $this->configService->getAllFeatureFlags();

        $this->assertTrue($flags['proactive_refresh']);
        $this->assertTrue($flags['live_validation']); // Overridden by environment
    }

    public function test_get_configuration_summary_returns_complete_config()
    {
        Config::set('token-refresh.features.proactive_refresh', true);
        Config::set('token-refresh.timing.proactive_refresh_minutes', 15);
        Config::set('token-refresh.notifications.enabled', true);

        $summary = $this->configService->getConfigurationSummary();

        $this->assertArrayHasKey('features', $summary);
        $this->assertArrayHasKey('timing', $summary);
        $this->assertArrayHasKey('notifications', $summary);
        $this->assertArrayHasKey('environment', $summary);
        
        $this->assertTrue($summary['features']['proactive_refresh']);
        $this->assertEquals(15, $summary['timing']['proactive_refresh_minutes']);
        $this->assertTrue($summary['notifications']['enabled']);
    }

    public function test_validate_configuration_returns_errors_for_invalid_values()
    {
        Config::set('token-refresh.timing.proactive_refresh_minutes', 0); // Invalid
        Config::set('token-refresh.timing.max_retry_attempts', 15); // Invalid (too high)
        Config::set('token-refresh.notifications.throttle_hours', 0); // Invalid

        $errors = $this->configService->validateConfiguration();

        $this->assertNotEmpty($errors);
        $this->assertContains('Proactive refresh minutes must be at least 1', $errors);
        $this->assertContains('Max retry attempts must be between 1 and 10', $errors);
        $this->assertContains('Notification throttle hours must be at least 1', $errors);
    }

    public function test_validate_configuration_returns_empty_for_valid_config()
    {
        Config::set('token-refresh.timing.proactive_refresh_minutes', 15);
        Config::set('token-refresh.timing.max_retry_attempts', 5);
        Config::set('token-refresh.notifications.throttle_hours', 24);
        Config::set('token-refresh.rate_limiting.max_attempts_per_hour', 10);
        Config::set('token-refresh.rate_limiting.max_health_checks_per_minute', 5);

        $errors = $this->configService->validateConfiguration();

        $this->assertEmpty($errors);
    }

    public function test_caching_works_correctly()
    {
        Config::set('token-refresh.features.proactive_refresh', true);
        Config::set('token-refresh.environments.testing.features.proactive_refresh', true);

        // First call should cache the result
        $result1 = $this->configService->isFeatureEnabled('proactive_refresh');
        
        // Change config (but cache should still return old value)
        Config::set('token-refresh.features.proactive_refresh', false);
        Config::set('token-refresh.environments.testing.features.proactive_refresh', false);
        $result2 = $this->configService->isFeatureEnabled('proactive_refresh');

        $this->assertTrue($result1);
        $this->assertTrue($result2); // Should still be true due to caching

        // Clear cache and try again
        $this->configService->clearConfigCache();
        $result3 = $this->configService->isFeatureEnabled('proactive_refresh');

        $this->assertFalse($result3); // Should now reflect the new config
    }

    public function test_environment_specific_config_overrides_default()
    {
        // Set up default config
        Config::set('token-refresh.features.proactive_refresh', true);
        Config::set('token-refresh.timing.proactive_refresh_minutes', 15);
        
        // Set up environment-specific config
        Config::set('token-refresh.environments.testing', [
            'features' => [
                'proactive_refresh' => false,
            ],
            'timing' => [
                'proactive_refresh_minutes' => 5,
            ],
        ]);
        
        // Mock environment
        app()->detectEnvironment(function () {
            return 'testing';
        });

        $this->assertFalse($this->configService->isFeatureEnabled('proactive_refresh'));
        $this->assertEquals(5, $this->configService->getTimingConfig('proactive_refresh_minutes'));
    }

    public function test_get_notification_template_returns_correct_template()
    {
        $templates = [
            'token_expired' => [
                'subject' => 'Token Expired',
                'view' => 'emails.token-expired',
            ],
        ];
        
        Config::set('token-refresh.notifications.templates', $templates);

        $template = $this->configService->getNotificationTemplate('token_expired');

        $this->assertEquals($templates['token_expired'], $template);
    }

    public function test_get_notification_template_returns_empty_for_unknown_type()
    {
        Config::set('token-refresh.notifications.templates', []);

        $template = $this->configService->getNotificationTemplate('unknown_type');

        $this->assertEquals([], $template);
    }
}