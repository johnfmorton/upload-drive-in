<?php

namespace Tests\Feature\Components;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

/**
 * Test suite for the Upload Page Section component.
 * 
 * This component displays the upload page URL and storage information
 * in a provider-agnostic way, adapting its display based on whether
 * the storage provider requires user-level authentication (OAuth) or
 * system-level authentication (API keys).
 */
class UploadPageSectionTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);

        $this->employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee@example.com',
            'owner_id' => $this->admin->id,
            'username' => 'testemployee',
        ]);
    }

    /**
     * Test component displays correctly for S3 provider.
     * 
     * Requirements: 1.2, 3.1, 3.2, 7.1, 7.2
     */
    public function test_component_displays_correctly_for_s3_provider()
    {
        // Configure Amazon S3 as the default provider
        Config::set('cloud-storage.default', 'amazon-s3');
        
        // Set upload URL for employee
        $uploadUrl = url('/upload/' . $this->employee->username);
        $this->employee->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify component header displays correctly
        $response->assertSee('Your Upload Page');
        
        // Verify generic cloud storage label is shown (not provider-specific)
        $response->assertSee('Cloud Storage');
        
        // Verify upload URL is displayed (check for the username part which is always present)
        $response->assertSee($this->employee->username);
        
        // Verify copy button is present
        $response->assertSee('Copy URL');
        
        // Verify the component uses neutral styling (no provider-specific branding)
        $response->assertSee('bg-gray-50 border border-gray-200');
    }

    /**
     * Test component shows system-level storage message for S3.
     * 
     * Requirements: 3.1, 3.2, 7.1, 7.2
     */
    public function test_component_shows_system_level_storage_message()
    {
        // Configure Amazon S3 as the default provider
        Config::set('cloud-storage.default', 'amazon-s3');
        
        // Set upload URL for employee
        $uploadUrl = url('/upload/' . $this->employee->username);
        $this->employee->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify system-level storage info message is displayed
        $response->assertSee('Files are stored in your organization\'s Amazon S3');
        
        // Verify admin contact message is displayed
        $response->assertSee('Contact your administrator for storage-related questions');
        
        // Verify the info message uses appropriate styling
        $response->assertSee('bg-blue-50 border border-blue-200');
    }

    /**
     * Test component displays upload URL with copy button.
     * 
     * Requirements: 1.2, 7.1, 7.2
     */
    public function test_component_displays_upload_url_with_copy_button()
    {
        // Configure Amazon S3 as the default provider
        Config::set('cloud-storage.default', 'amazon-s3');
        
        // Set upload URL for employee
        $uploadUrl = url('/upload/' . $this->employee->username);
        $this->employee->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify upload URL is displayed in a code element
        $response->assertSee('<code', false);
        $response->assertSee($this->employee->username);
        
        // Verify copy button is present with correct attributes
        $response->assertSee('copyUploadUrl');
        $response->assertSee('Copy URL');
        
        // Verify accessibility attributes are present
        $response->assertSee('aria-label');
        $response->assertSee('role=', false);
        $response->assertSee('tabindex=', false);
        
        // Verify helper text is displayed
        $response->assertSee('Share this URL with clients');
    }

    /**
     * Test component handles missing upload URL gracefully.
     * 
     * Requirements: 1.2, 7.1, 7.2
     */
    public function test_component_handles_missing_upload_url()
    {
        // Configure Amazon S3 as the default provider
        Config::set('cloud-storage.default', 'amazon-s3');
        
        // Ensure employee has no upload URL
        $this->employee->update(['upload_url' => null]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify error message is displayed
        $response->assertSee('Upload page is not available');
        $response->assertSee('Please contact your administrator');
        
        // Verify error styling is used
        $response->assertSee('bg-yellow-50');
        
        // Verify copy button is NOT displayed when URL is missing
        $response->assertDontSee('Copy URL');
        
        // Verify system-level storage message is NOT displayed when URL is missing
        $response->assertDontSee('Files are stored in your organization');
    }

    /**
     * Test component displays differently for OAuth providers (Google Drive).
     * 
     * Requirements: 1.2, 3.1
     */
    public function test_component_displays_differently_for_oauth_providers()
    {
        // Configure Google Drive as the default provider
        Config::set('cloud-storage.default', 'google-drive');
        
        // Set upload URL for employee
        $uploadUrl = url('/upload/' . $this->employee->username);
        $this->employee->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // For OAuth providers, the upload URL should be in the Google Drive connection component
        // The standalone upload page section should not show system-level storage message
        // Verify provider-specific display name is shown
        $response->assertSee('Google Drive');
        
        // Verify system-level storage message is NOT shown for OAuth providers
        $response->assertDontSee('Files are stored in your organization\'s Amazon S3');
    }

    /**
     * Test component shows provider display name correctly.
     * 
     * Requirements: 3.1, 7.1
     */
    public function test_component_shows_provider_display_name_correctly()
    {
        // Test with S3
        Config::set('cloud-storage.default', 'amazon-s3');
        $this->employee->update(['upload_url' => url('/upload/' . $this->employee->username)]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        $response->assertSee('Amazon S3');
        
        // Test with Microsoft Teams (OAuth provider - won't show in upload page section)
        // For OAuth providers, the upload URL is shown in the connection component
        Config::set('cloud-storage.default', 'microsoft-teams');
        
        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        // Microsoft Teams is an OAuth provider, so it won't show in the standalone upload page section
        // It would show in the connection component instead
        $response->assertDontSee('Amazon S3');
    }

    /**
     * Test component handles configuration errors gracefully.
     * 
     * Requirements: 7.1, 7.2
     */
    public function test_component_handles_configuration_errors()
    {
        // Set invalid provider configuration
        Config::set('cloud-storage.default', 'invalid-provider');
        
        // Set upload URL for employee
        $uploadUrl = url('/upload/' . $this->employee->username);
        $this->employee->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify error message is displayed
        $response->assertSee('Cloud storage is not properly configured');
        $response->assertSee('Please contact your administrator');
        
        // Verify error styling is used
        $response->assertSee('bg-red-50');
    }

    /**
     * Test component accessibility features.
     * 
     * Requirements: 7.1, 7.2
     */
    public function test_component_accessibility_features()
    {
        // Configure Amazon S3 as the default provider
        Config::set('cloud-storage.default', 'amazon-s3');
        
        // Set upload URL for employee
        $uploadUrl = url('/upload/' . $this->employee->username);
        $this->employee->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify ARIA attributes for the URL display
        $response->assertSee('role=', false);
        $response->assertSee('aria-readonly', false);
        
        // Verify ARIA attributes for the copy button
        $response->assertSee('aria-label', false);
        $response->assertSee('aria-pressed', false);
        
        // Verify screen reader announcement area
        $response->assertSee('aria-live', false);
        $response->assertSee('aria-atomic', false);
        $response->assertSee('sr-only');
        
        // Verify keyboard navigation support
        $response->assertSee('handleKeydown');
        $response->assertSee('tabindex', false);
    }

    /**
     * Test component copy functionality JavaScript is present.
     * 
     * Requirements: 7.1, 7.2
     */
    public function test_component_copy_functionality_javascript_present()
    {
        // Configure Amazon S3 as the default provider
        Config::set('cloud-storage.default', 'amazon-s3');
        
        // Set upload URL for employee
        $uploadUrl = url('/upload/' . $this->employee->username);
        $this->employee->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify Alpine.js data structure is present
        $response->assertSee('x-data', false);
        $response->assertSee('copiedUploadUrl');
        $response->assertSee('copyUploadUrl');
        
        // Verify copy button click handler
        $response->assertSee('copyUploadUrl');
        
        // Verify keyboard handler
        $response->assertSee('handleKeydown');
        
        // Verify clipboard API usage
        $response->assertSee('navigator.clipboard.writeText');
        
        // Verify success/error feedback
        $response->assertSee('copiedUploadUrl');
    }

    /**
     * Test component responsive design elements.
     * 
     * Requirements: 7.1, 7.2
     */
    public function test_component_responsive_design()
    {
        // Configure Amazon S3 as the default provider
        Config::set('cloud-storage.default', 'amazon-s3');
        
        // Set upload URL for employee
        $uploadUrl = url('/upload/' . $this->employee->username);
        $this->employee->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify responsive padding classes
        $response->assertSee('p-4 sm:p-8');
        
        // Verify responsive shadow and rounding
        $response->assertSee('shadow sm:rounded-lg');
        
        // Verify flexible layout for URL and button
        $response->assertSee('flex items-center justify-between');
        $response->assertSee('flex-1');
        
        // Verify text truncation for long URLs
        $response->assertSee('truncate');
        
        // Verify button doesn't wrap
        $response->assertSee('whitespace-nowrap');
    }

    /**
     * Test component displays correct icons for different providers.
     * 
     * Requirements: 3.1, 7.1
     */
    public function test_component_displays_correct_icons()
    {
        // Set upload URL for employee
        $uploadUrl = url('/upload/' . $this->employee->username);
        $this->employee->update(['upload_url' => $uploadUrl]);

        // Test with S3 (system-level) - should show generic cloud icon
        Config::set('cloud-storage.default', 'amazon-s3');
        
        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify generic cloud icon SVG is present
        $response->assertSee('M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999');
        
        // Test with Google Drive (OAuth) - should show Google Drive icon
        Config::set('cloud-storage.default', 'google-drive');
        
        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify Google Drive icon SVG is present
        $response->assertSee('M12.545,10.239v3.821h5.445c-0.712,2.315');
    }

    /**
     * Test component layout consistency across providers.
     * 
     * Requirements: 1.2, 3.1, 7.1
     */
    public function test_component_layout_consistency_across_providers()
    {
        // Set upload URL for employee
        $uploadUrl = url('/upload/' . $this->employee->username);
        $this->employee->update(['upload_url' => $uploadUrl]);

        // Test with S3
        Config::set('cloud-storage.default', 'amazon-s3');
        $s3Response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        // Test with Google Drive
        Config::set('cloud-storage.default', 'google-drive');
        $gdResponse = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        // Both should have the same base structure
        $s3Response->assertStatus(200);
        $gdResponse->assertStatus(200);
        
        // Both should show the upload URL (check for username which is part of URL)
        $s3Response->assertSee($this->employee->username);
        $gdResponse->assertSee($this->employee->username);
        
        // Both should have copy functionality
        $s3Response->assertSee('Copy URL');
        $gdResponse->assertSee('Copy URL');
        
        // Both should have consistent container styling
        $s3Response->assertSee('bg-white');
        $gdResponse->assertSee('bg-white');
    }
}
