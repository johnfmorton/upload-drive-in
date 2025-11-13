<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\CloudStorageProviderAvailabilityService;
use App\Enums\ProviderAvailabilityStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnhancedConfigurationDropdownTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private CloudStorageProviderAvailabilityService $availabilityService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        
        $this->availabilityService = app(CloudStorageProviderAvailabilityService::class);
    }

    /** @test */
    public function admin_can_view_cloud_storage_configuration_with_enhanced_dropdown()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        
        // Check that the enhanced dropdown elements are present
        $response->assertSee('provider-select enhanced-provider-select');
        $response->assertSee('provider-selection-help');
        $response->assertSee('Select cloud storage provider');
    }

    /** @test */
    public function dropdown_defaults_to_google_drive_when_available()
    {
        // Ensure Google Drive is available
        config(['cloud-storage.provider_availability.google-drive' => 'fully_available']);
        
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        
        // Check that Google Drive is selected by default
        $response->assertSee('value="google-drive"', false);
        $response->assertSee('selected', false);
    }

    /** @test */
    public function coming_soon_providers_are_shown_as_disabled()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        
        // Check that coming soon providers are marked as disabled
        $response->assertSee('disabled');
        $response->assertSee('Coming Soon');
    }

    /** @test */
    public function cannot_select_unavailable_provider_as_default()
    {
        // Try to set a coming soon provider as default
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.cloud-storage.default'), [
                'default_provider' => 'microsoft-teams' // This is coming soon
            ]);

        $response->assertSessionHasErrors('default_provider');
        $response->assertRedirect();
    }

    /** @test */
    public function can_select_available_provider_as_default()
    {
        // Try to set Google Drive as default (should be available)
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.cloud-storage.default'), [
                'default_provider' => 'google-drive'
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function provider_availability_service_returns_correct_configuration()
    {
        $config = $this->availabilityService->getProviderConfigurationForFrontend();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('google-drive', $config);
        
        // Google Drive should be available
        $this->assertTrue($config['google-drive']['selectable']);
        $this->assertEquals('Available', $config['google-drive']['status_label']);
        
        // Check that coming soon providers are not selectable
        foreach ($config as $provider => $data) {
            if ($data['status'] === 'coming_soon') {
                $this->assertFalse($data['selectable'], "Provider {$provider} should not be selectable");
            }
        }
    }

    /** @test */
    public function visual_indicators_show_correct_provider_status()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        
        // Check for visual indicator elements
        $response->assertSee('provider-help-text', false);
        $response->assertSee('role="status"', false);
        $response->assertSee('aria-live="polite"', false);
    }

    /** @test */
    public function accessibility_attributes_are_present()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        
        // Check for accessibility attributes
        $response->assertSee('provider-selection-help');
        $response->assertSee('combobox');
        $response->assertSee('Select cloud storage provider');
        $response->assertSee('status');
        $response->assertSee('polite');
    }
}