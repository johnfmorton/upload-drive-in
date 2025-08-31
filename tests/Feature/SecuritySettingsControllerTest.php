<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DomainAccessRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecuritySettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);
    }

    public function test_admin_can_access_security_settings_page()
    {
        $response = $this->actingAs($this->admin)->get('/admin/security-settings');

        $response->assertStatus(200);
        $response->assertViewIs('admin.security.settings');
        $response->assertViewHas('settings');
    }

    public function test_security_settings_page_shows_default_settings_when_none_exist()
    {
        $response = $this->actingAs($this->admin)->get('/admin/security-settings');

        $response->assertStatus(200);
        $response->assertViewHas('settings', function ($settings) {
            return $settings->mode === 'blacklist' &&
                   $settings->rules === [] &&
                   $settings->allow_public_registration === true;
        });
    }

    public function test_security_settings_page_shows_existing_settings()
    {
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['example.com', '*.trusted.com'],
            'allow_public_registration' => false,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/security-settings');

        $response->assertStatus(200);
        $response->assertViewHas('settings', function ($settings) {
            return $settings->mode === 'whitelist' &&
                   $settings->rules === ['example.com', '*.trusted.com'] &&
                   $settings->allow_public_registration === false;
        });
    }

    public function test_non_admin_cannot_access_security_settings()
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT]);

        $response = $this->actingAs($client)->get('/admin/security-settings');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_security_settings()
    {
        $response = $this->get('/admin/security-settings');

        $response->assertRedirect('/login');
    }

    public function test_admin_can_enable_public_registration()
    {
        DomainAccessRule::create([
            'mode' => 'blacklist',
            'rules' => [],
            'allow_public_registration' => false,
        ]);

        $response = $this->actingAs($this->admin)->put('/admin/security-settings/registration', [
            'allow_public_registration' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', __('messages.registration_security_updated'));

        $this->assertDatabaseHas('domain_access_rules', [
            'allow_public_registration' => true,
        ]);
    }

    public function test_admin_can_disable_public_registration()
    {
        DomainAccessRule::create([
            'mode' => 'blacklist',
            'rules' => [],
            'allow_public_registration' => true,
        ]);

        $response = $this->actingAs($this->admin)->put('/admin/security-settings/registration');

        $response->assertRedirect();
        $response->assertSessionHas('status', __('messages.registration_security_updated'));

        $this->assertDatabaseHas('domain_access_rules', [
            'allow_public_registration' => false,
        ]);
    }

    public function test_registration_setting_creates_domain_access_rule_if_none_exists()
    {
        $this->assertDatabaseCount('domain_access_rules', 0);

        $response = $this->actingAs($this->admin)->put('/admin/security-settings/registration', [
            'allow_public_registration' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', __('messages.registration_security_updated'));

        $this->assertDatabaseCount('domain_access_rules', 1);
        $this->assertDatabaseHas('domain_access_rules', [
            'allow_public_registration' => true,
        ]);
    }

    public function test_admin_can_update_domain_rules_to_blacklist_mode()
    {
        $response = $this->actingAs($this->admin)->put('/admin/security-settings/domain-rules', [
            'access_control_mode' => 'blacklist',
            'domain_rules' => "spam.com\n*.malicious.com\nbad@example.com",
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', __('messages.access_control_rules_updated'));

        $this->assertDatabaseHas('domain_access_rules', [
            'mode' => 'blacklist',
            'rules' => json_encode(['spam.com', '*.malicious.com', 'bad@example.com']),
        ]);
    }

    public function test_admin_can_update_domain_rules_to_whitelist_mode()
    {
        $response = $this->actingAs($this->admin)->put('/admin/security-settings/domain-rules', [
            'access_control_mode' => 'whitelist',
            'domain_rules' => "trusted.com\n*.company.com\nuser@allowed.com",
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', __('messages.access_control_rules_updated'));

        $this->assertDatabaseHas('domain_access_rules', [
            'mode' => 'whitelist',
            'rules' => json_encode(['trusted.com', '*.company.com', 'user@allowed.com']),
        ]);
    }

    public function test_domain_rules_can_be_empty()
    {
        $response = $this->actingAs($this->admin)->put('/admin/security-settings/domain-rules', [
            'access_control_mode' => 'blacklist',
            'domain_rules' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', __('messages.access_control_rules_updated'));

        $this->assertDatabaseHas('domain_access_rules', [
            'mode' => 'blacklist',
            'rules' => json_encode([]),
        ]);
    }

    public function test_domain_rules_filters_empty_lines()
    {
        $response = $this->actingAs($this->admin)->put('/admin/security-settings/domain-rules', [
            'access_control_mode' => 'blacklist',
            'domain_rules' => "example.com\n\n\n*.test.com\n   \nbad.com",
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', __('messages.access_control_rules_updated'));

        // Check that the record exists and has the correct mode
        $this->assertDatabaseHas('domain_access_rules', [
            'mode' => 'blacklist',
        ]);

        // Verify the rules array contains the expected values (order may vary due to array_filter)
        $domainRule = DomainAccessRule::first();
        $this->assertNotNull($domainRule);
        $this->assertEquals('blacklist', $domainRule->mode);
        $this->assertCount(3, $domainRule->rules);
        $this->assertContains('example.com', $domainRule->rules);
        $this->assertContains('*.test.com', $domainRule->rules);
        $this->assertContains('bad.com', $domainRule->rules);
    }

    public function test_domain_rules_creates_record_if_none_exists()
    {
        $this->assertDatabaseCount('domain_access_rules', 0);

        $response = $this->actingAs($this->admin)->put('/admin/security-settings/domain-rules', [
            'access_control_mode' => 'whitelist',
            'domain_rules' => 'trusted.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', __('messages.access_control_rules_updated'));

        $this->assertDatabaseCount('domain_access_rules', 1);
        $this->assertDatabaseHas('domain_access_rules', [
            'mode' => 'whitelist',
            'rules' => json_encode(['trusted.com']),
        ]);
    }

    public function test_domain_rules_validation_requires_access_control_mode()
    {
        $response = $this->actingAs($this->admin)->put('/admin/security-settings/domain-rules', [
            'domain_rules' => 'example.com',
        ]);

        $response->assertSessionHasErrors(['access_control_mode']);
    }

    public function test_domain_rules_validation_requires_valid_access_control_mode()
    {
        $response = $this->actingAs($this->admin)->put('/admin/security-settings/domain-rules', [
            'access_control_mode' => 'invalid_mode',
            'domain_rules' => 'example.com',
        ]);

        $response->assertSessionHasErrors(['access_control_mode']);
    }

    public function test_domain_rules_validation_rejects_invalid_domain_format()
    {
        $response = $this->actingAs($this->admin)->put('/admin/security-settings/domain-rules', [
            'access_control_mode' => 'blacklist',
            'domain_rules' => "valid.com\ninvalid..domain\n*.good.com",
        ]);

        $response->assertSessionHasErrors(['domain_rules']);
    }

    public function test_domain_rules_validation_accepts_valid_domain_formats()
    {
        $validRules = [
            'example.com',
            '*.subdomain.com',
            'user@domain.com',
            'test-domain.co.uk',
            'a.b.c.d.com',
        ];

        $response = $this->actingAs($this->admin)->put('/admin/security-settings/domain-rules', [
            'access_control_mode' => 'blacklist',
            'domain_rules' => implode("\n", $validRules),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status', __('messages.access_control_rules_updated'));
    }

    public function test_domain_rules_validation_rejects_invalid_email_format()
    {
        $response = $this->actingAs($this->admin)->put('/admin/security-settings/domain-rules', [
            'access_control_mode' => 'blacklist',
            'domain_rules' => "valid.com\ninvalid@email@domain.com\n*.good.com",
        ]);

        $response->assertSessionHasErrors(['domain_rules']);
    }

    public function test_non_admin_cannot_update_registration_settings()
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT]);

        $response = $this->actingAs($client)->put('/admin/security-settings/registration', [
            'allow_public_registration' => '1',
        ]);

        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_update_domain_rules()
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT]);

        $response = $this->actingAs($client)->put('/admin/security-settings/domain-rules', [
            'access_control_mode' => 'blacklist',
            'domain_rules' => 'spam.com',
        ]);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_registration_settings()
    {
        $response = $this->put('/admin/security-settings/registration', [
            'allow_public_registration' => '1',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_update_domain_rules()
    {
        $response = $this->put('/admin/security-settings/domain-rules', [
            'access_control_mode' => 'blacklist',
            'domain_rules' => 'spam.com',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_client_creation_functionality_is_not_available()
    {
        // Verify that the old client creation route doesn't exist
        $response = $this->actingAs($this->admin)->post('/admin/security-settings/clients', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
        ]);

        $response->assertStatus(404);
    }

    public function test_security_settings_controller_does_not_have_create_client_method()
    {
        $controller = new \App\Http\Controllers\Admin\SecuritySettingsController();
        
        $this->assertFalse(
            method_exists($controller, 'createClient'),
            'SecuritySettingsController should not have createClient method'
        );
    }

    public function test_security_settings_view_does_not_contain_client_creation_form()
    {
        $response = $this->actingAs($this->admin)->get('/admin/security-settings');

        $response->assertStatus(200);
        $response->assertDontSee('Create Client User');
        $response->assertDontSee('client_name');
        $response->assertDontSee('client_email');
    }
}