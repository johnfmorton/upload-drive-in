<?php

namespace Tests\Browser;

use App\Models\User;
use App\Enums\UserRole;
use App\Services\CloudStorageManager;
use App\Services\CloudConfigurationService;
use App\Services\CloudStorageHealthService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminProviderManagementTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email_verified_at' => now(),
            'two_factor_enabled' => false, // Disable 2FA for tests
        ]);
    }

    public function test_admin_can_access_provider_management_page()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/cloud-storage/provider-management')
                    ->assertSee('Provider Management')
                    ->assertSee('Back to Configuration');
        });
    }

    public function test_provider_cards_display_correctly()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/cloud-storage/provider-management')
                    ->waitFor('.bg-white.overflow-hidden.shadow-xl', 10)
                    ->assertSee('Google Drive')
                    ->assertSee('Amazon S3')
                    ->within('.bg-white.overflow-hidden.shadow-xl:first-child', function ($card) {
                        $card->assertSee('Auth Type:')
                             ->assertSee('Storage Model:');
                    });
        });
    }

    public function test_provider_details_modal_opens_and_closes()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/cloud-storage/provider-management')
                    ->waitFor('button:contains("Details")', 10)
                    ->click('button:contains("Details")')
                    ->waitFor('[data-modal-name="provider-details"]', 10)
                    ->assertSee('Provider Details')
                    ->assertSee('Basic Information')
                    ->assertSee('Status')
                    ->click('button:contains("Close")')
                    ->waitUntilMissing('[data-modal-name="provider-details"]', 5);
        });
    }

    public function test_provider_test_functionality()
    {
        // Mock the CloudStorageManager to avoid actual API calls
        $this->mock(CloudStorageManager::class, function ($mock) {
            $mock->shouldReceive('getAvailableProviders')
                 ->andReturn(['google-drive', 'amazon-s3']);
        });

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/cloud-storage/provider-management')
                    ->waitFor('button:contains("Test")', 10);
            
            // Check if test button exists (it should only exist for configured providers)
            if ($browser->element('button:contains("Test")')) {
                $browser->click('button:contains("Test")')
                        ->waitFor('[data-modal-name="test-results"]', 10)
                        ->assertSee('Test Results')
                        ->click('button:contains("Close")')
                        ->waitUntilMissing('[data-modal-name="test-results"]', 5);
            }
        });
    }

    public function test_set_current_provider_functionality()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/cloud-storage/provider-management')
                    ->waitFor('.bg-white.overflow-hidden.shadow-xl', 10);
            
            // Check if "Set as Current" button exists
            if ($browser->element('button:contains("Set as Current")')) {
                $browser->click('button:contains("Set as Current")')
                        ->waitFor('.fixed.top-4.right-4', 10) // Wait for notification
                        ->assertSee('Provider preference updated successfully');
            }
        });
    }

    public function test_provider_status_indicators_display()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/cloud-storage/provider-management')
                    ->waitFor('.bg-white.overflow-hidden.shadow-xl', 10)
                    ->within('.bg-white.overflow-hidden.shadow-xl:first-child', function ($card) {
                        // Should see at least one status indicator
                        $card->assertSeeIn('.px-2.py-1.text-xs', 'Not Configured');
                    });
        });
    }

    public function test_provider_capabilities_display_in_details()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/cloud-storage/provider-management')
                    ->waitFor('button:contains("Details")', 10)
                    ->click('button:contains("Details")')
                    ->waitFor('[data-modal-name="provider-details"]', 10)
                    ->pause(2000) // Wait for AJAX to load provider details
                    ->assertSee('Capabilities');
        });
    }

    public function test_health_status_displays_when_available()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/cloud-storage/provider-management')
                    ->waitFor('.bg-white.overflow-hidden.shadow-xl', 10);
            
            // Look for health status sections
            if ($browser->element('.bg-green-50, .bg-yellow-50, .bg-orange-50, .bg-red-50')) {
                $browser->assertSee('Health Status');
            }
        });
    }

    public function test_modal_z_index_hierarchy()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/cloud-storage/provider-management')
                    ->waitFor('button:contains("Details")', 10)
                    ->click('button:contains("Details")')
                    ->waitFor('[data-modal-name="provider-details"]', 10)
                    ->assertAttribute('[data-modal-type="backdrop"]', 'data-z-index', '9998')
                    ->assertAttribute('[data-modal-type="container"]', 'data-z-index', '9999')
                    ->assertAttribute('[data-modal-type="content"]', 'data-z-index', '10000');
        });
    }

    public function test_responsive_design_on_mobile()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667) // iPhone SE size
                    ->loginAs($this->adminUser)
                    ->visit('/admin/cloud-storage/provider-management')
                    ->waitFor('.grid.grid-cols-1', 10)
                    ->assertSee('Provider Management')
                    ->assertSee('Google Drive');
        });
    }

    public function test_error_handling_for_invalid_provider()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/cloud-storage/providers/invalid-provider/details')
                    ->assertSee('404'); // Should get 404 for invalid provider
        });
    }

    public function test_javascript_functions_work_correctly()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/cloud-storage/provider-management')
                    ->waitFor('button:contains("Details")', 10)
                    ->click('button:contains("Details")')
                    ->waitFor('[data-modal-name="provider-details"]', 10)
                    ->script([
                        'window.testFormatBytes = function(bytes) { 
                            return window.Alpine.evaluate(document.querySelector("[x-data]"), "formatBytes(" + bytes + ")"); 
                        }'
                    ])
                    ->assertScript('window.testFormatBytes(1024)', '1 KB')
                    ->assertScript('window.testFormatBytes(1048576)', '1 MB');
        });
    }

    public function test_back_to_configuration_link_works()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/cloud-storage/provider-management')
                    ->click('a:contains("Back to Configuration")')
                    ->assertUrlIs('/admin/cloud-storage')
                    ->assertSee('Cloud Storage Configuration');
        });
    }

    public function test_provider_management_requires_admin_role()
    {
        $clientUser = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($clientUser) {
            $browser->loginAs($clientUser)
                    ->visit('/admin/cloud-storage/provider-management')
                    ->assertSee('403'); // Should get 403 Forbidden
        });
    }

    public function test_notification_system_works()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/cloud-storage/provider-management')
                    ->script([
                        'window.Alpine.evaluate(document.querySelector("[x-data]"), "showSuccess(\'Test notification\')")'
                    ])
                    ->waitFor('.fixed.top-4.right-4.bg-green-100', 5)
                    ->assertSee('Test notification')
                    ->pause(3500) // Wait for auto-dismiss
                    ->assertDontSee('Test notification');
        });
    }

    public function test_loading_states_display_correctly()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/cloud-storage/provider-management')
                    ->waitFor('button:contains("Details")', 10)
                    ->click('button:contains("Details")')
                    ->waitFor('[data-modal-name="provider-details"]', 10)
                    ->assertSee('Loading') // Should see loading spinner initially
                    ->pause(2000) // Wait for loading to complete
                    ->assertDontSee('Loading'); // Loading should be gone
        });
    }
}