<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\TokenRefreshConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TokenRefreshConfigControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->regularUser = User::factory()->create(['role' => UserRole::CLIENT]);

        // Enable admin interface
        Config::set('token-refresh.admin_interface.enabled', true);
        Config::set('token-refresh.admin_interface.allow_runtime_changes', true);
        Config::set('token-refresh.admin_interface.modifiable_settings', [
            'features.proactive_refresh',
            'timing.proactive_refresh_minutes',
            'notifications.throttle_hours',
        ]);

        Cache::flush();
    }

    public function test_admin_can_access_config_page()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.token-refresh.config'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.token-refresh.config');
        $response->assertViewHas('configuration');
    }

    public function test_non_admin_cannot_access_config_page()
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('admin.token-refresh.config'));

        $response->assertStatus(403);
    }

    public function test_config_page_returns_404_when_disabled()
    {
        Config::set('token-refresh.admin_interface.enabled', false);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.token-refresh.config'));

        $response->assertStatus(404);
    }

    public function test_admin_can_toggle_feature_flag()
    {
        Config::set('token-refresh.features.proactive_refresh', true);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.token-refresh.toggle-feature'), [
                'feature' => 'proactive_refresh',
                'enabled' => false,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'enabled' => false,
        ]);

        $this->assertFalse(config('token-refresh.features.proactive_refresh'));
    }

    public function test_toggle_feature_requires_confirmation_for_critical_features()
    {
        Config::set('token-refresh.admin_interface.require_confirmation', [
            'features.proactive_refresh',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.token-refresh.toggle-feature'), [
                'feature' => 'proactive_refresh',
                'enabled' => false,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'requires_confirmation' => true,
        ]);
    }

    public function test_toggle_feature_with_confirmation_succeeds()
    {
        Config::set('token-refresh.admin_interface.require_confirmation', [
            'features.proactive_refresh',
        ]);
        Config::set('token-refresh.features.proactive_refresh', true);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.token-refresh.toggle-feature'), [
                'feature' => 'proactive_refresh',
                'enabled' => false,
                'confirmed' => true,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'enabled' => false,
        ]);

        $this->assertFalse(config('token-refresh.features.proactive_refresh'));
    }

    public function test_admin_can_update_setting()
    {
        Config::set('token-refresh.timing.proactive_refresh_minutes', 15);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.token-refresh.update-setting'), [
                'key' => 'timing.proactive_refresh_minutes',
                'value' => 10,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'new_value' => 10,
        ]);

        $this->assertEquals(10, config('token-refresh.timing.proactive_refresh_minutes'));
    }

    public function test_update_setting_validates_input()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.token-refresh.update-setting'), [
                'key' => 'timing.proactive_refresh_minutes',
                'value' => 0, // Invalid value
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);
    }

    public function test_update_setting_requires_confirmation_for_critical_settings()
    {
        Config::set('token-refresh.admin_interface.require_confirmation', [
            'timing.proactive_refresh_minutes',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.token-refresh.update-setting'), [
                'key' => 'timing.proactive_refresh_minutes',
                'value' => 5,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'requires_confirmation' => true,
        ]);
    }

    public function test_admin_can_clear_cache()
    {
        // Set up some cache entries
        Cache::put('token_refresh_config:feature:proactive_refresh', true, 300);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.token-refresh.clear-cache'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertFalse(Cache::has('token_refresh_config:feature:proactive_refresh'));
    }

    public function test_admin_can_get_status()
    {
        Config::set('token-refresh.features.proactive_refresh', true);
        Config::set('token-refresh.timing.proactive_refresh_minutes', 15);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.token-refresh.status'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'configuration' => [
                'features',
                'timing',
                'notifications',
                'environment',
            ],
            'validation_errors',
            'environment',
            'allow_runtime_changes',
        ]);
    }

    public function test_operations_fail_when_runtime_changes_disabled()
    {
        Config::set('token-refresh.admin_interface.allow_runtime_changes', false);

        $toggleResponse = $this->actingAs($this->adminUser)
            ->postJson(route('admin.token-refresh.toggle-feature'), [
                'feature' => 'proactive_refresh',
                'enabled' => false,
            ]);

        $updateResponse = $this->actingAs($this->adminUser)
            ->postJson(route('admin.token-refresh.update-setting'), [
                'key' => 'timing.proactive_refresh_minutes',
                'value' => 10,
            ]);

        $toggleResponse->assertStatus(403);
        $updateResponse->assertStatus(403);
    }

    public function test_operations_fail_for_non_modifiable_settings()
    {
        Config::set('token-refresh.admin_interface.modifiable_settings', [
            'features.proactive_refresh', // Only this is modifiable
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.token-refresh.update-setting'), [
                'key' => 'timing.max_retry_attempts', // Not in modifiable list
                'value' => 3,
            ]);

        $response->assertStatus(500);
    }

    public function test_validation_errors_are_returned_in_status()
    {
        Config::set('token-refresh.timing.proactive_refresh_minutes', 0); // Invalid

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.token-refresh.status'));

        $response->assertStatus(200);
        $response->assertJsonPath('validation_errors.0', 'Proactive refresh minutes must be at least 1');
    }

    public function test_setting_validation_prevents_invalid_values()
    {
        $testCases = [
            ['key' => 'timing.proactive_refresh_minutes', 'value' => 0, 'error' => 'between 1 and 60'],
            ['key' => 'timing.proactive_refresh_minutes', 'value' => 70, 'error' => 'between 1 and 60'],
            ['key' => 'timing.max_retry_attempts', 'value' => 0, 'error' => 'between 1 and 10'],
            ['key' => 'timing.max_retry_attempts', 'value' => 15, 'error' => 'between 1 and 10'],
            ['key' => 'notifications.throttle_hours', 'value' => 0, 'error' => 'between 1 and 168'],
            ['key' => 'rate_limiting.max_attempts_per_hour', 'value' => 0, 'error' => 'between 1 and 100'],
        ];

        foreach ($testCases as $testCase) {
            Config::set('token-refresh.admin_interface.modifiable_settings', [$testCase['key']]);

            $response = $this->actingAs($this->adminUser)
                ->postJson(route('admin.token-refresh.update-setting'), [
                    'key' => $testCase['key'],
                    'value' => $testCase['value'],
                ]);

            $response->assertStatus(422);
            $response->assertJsonPath('error', function ($error) use ($testCase) {
                return str_contains($error, $testCase['error']);
            });
        }
    }

    public function test_non_admin_cannot_access_any_endpoints()
    {
        $endpoints = [
            ['method' => 'get', 'route' => 'admin.token-refresh.config'],
            ['method' => 'post', 'route' => 'admin.token-refresh.toggle-feature'],
            ['method' => 'post', 'route' => 'admin.token-refresh.update-setting'],
            ['method' => 'post', 'route' => 'admin.token-refresh.clear-cache'],
            ['method' => 'get', 'route' => 'admin.token-refresh.status'],
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->actingAs($this->regularUser);
            
            if ($endpoint['method'] === 'get') {
                $response = $response->get(route($endpoint['route']));
            } else {
                $response = $response->postJson(route($endpoint['route']), []);
            }

            $response->assertStatus(403);
        }
    }

    public function test_guest_cannot_access_any_endpoints()
    {
        $endpoints = [
            ['method' => 'get', 'route' => 'admin.token-refresh.config'],
            ['method' => 'post', 'route' => 'admin.token-refresh.toggle-feature'],
            ['method' => 'post', 'route' => 'admin.token-refresh.update-setting'],
            ['method' => 'post', 'route' => 'admin.token-refresh.clear-cache'],
            ['method' => 'get', 'route' => 'admin.token-refresh.status'],
        ];

        foreach ($endpoints as $endpoint) {
            if ($endpoint['method'] === 'get') {
                $response = $this->get(route($endpoint['route']));
            } else {
                $response = $this->postJson(route($endpoint['route']), []);
            }

            $response->assertStatus(302); // Redirect to login
        }
    }

    public function test_configuration_summary_includes_environment_info()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.token-refresh.config'));

        $response->assertStatus(200);
        $response->assertViewHas('configuration.environment');
    }

    public function test_value_conversion_works_correctly()
    {
        Config::set('token-refresh.admin_interface.modifiable_settings', [
            'features.proactive_refresh',
            'timing.proactive_refresh_minutes',
        ]);

        // Test boolean conversion
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.token-refresh.toggle-feature'), [
                'feature' => 'proactive_refresh',
                'enabled' => 'true', // String that should be converted to boolean
            ]);

        $response->assertStatus(200);
        $this->assertTrue(config('token-refresh.features.proactive_refresh'));

        // Test integer conversion
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.token-refresh.update-setting'), [
                'key' => 'timing.proactive_refresh_minutes',
                'value' => '10', // String that should be converted to integer
            ]);

        $response->assertStatus(200);
        $this->assertSame(10, config('token-refresh.timing.proactive_refresh_minutes'));
    }
}