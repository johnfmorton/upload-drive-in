<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Models\CloudStorageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleDriveCopyFeedbackIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function setupGoogleDriveEnvironment($user)
    {
        // Create Google Drive token for user
        GoogleDriveToken::create([
            'user_id' => $user->id,
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'expires_at' => now()->addHour(),
            'token_type' => 'Bearer',
            'scopes' => ['https://www.googleapis.com/auth/drive.file']
        ]);

        // Configure Google Drive app settings (use updateOrCreate to avoid duplicates)
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_id', 'user_id' => null],
            ['value' => 'test_client_id']
        );

        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_secret', 'user_id' => null],
            ['value' => 'test_client_secret']
        );
    }

    public function test_admin_dashboard_renders_enhanced_copy_component()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com'
        ]);

        $this->setupGoogleDriveEnvironment($user);
        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);

        // Check for Alpine.js data structure
        $response->assertSee('x-data="{', false);
        $response->assertSee('copiedUploadUrl: false', false);
        $response->assertSee('copyUploadUrl(url)', false);
        $response->assertSee('handleKeydown(event, url)', false);

        // Check for Alpine.js event handlers
        $response->assertSee('@click="copyUploadUrl(', false);
        $response->assertSee('@keydown="handleKeydown(', false);

        // Check for conditional display directives
        $response->assertSee('x-show="!copiedUploadUrl"', false);
        $response->assertSee('x-show="copiedUploadUrl"', false);

        // Verify no legacy JavaScript
        $response->assertDontSee('@push(\'scripts\')', false);
        $response->assertDontSee('function copyUploadUrl(url)', false);
    }

    public function test_employee_dashboard_renders_enhanced_copy_component()
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'username' => 'testemployee',
            'email' => 'employee@example.com'
        ]);

        $this->setupGoogleDriveEnvironment($user);
        $this->actingAs($user);

        $response = $this->get('/employee/testemployee/dashboard');

        $response->assertStatus(200);

        // Check for Alpine.js data structure
        $response->assertSee('x-data="{', false);
        $response->assertSee('copiedUploadUrl: false', false);
        $response->assertSee('copyUploadUrl(url)', false);
        $response->assertSee('handleKeydown(event, url)', false);

        // Check for Alpine.js event handlers
        $response->assertSee('@click="copyUploadUrl(', false);
        $response->assertSee('@keydown="handleKeydown(', false);
    }

    public function test_component_includes_proper_alpine_js_structure()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com'
        ]);

        $this->setupGoogleDriveEnvironment($user);
        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);

        // Verify Alpine.js data properties
        $response->assertSee('copiedUploadUrl: false', false);

        // Verify Alpine.js methods
        $response->assertSee('copyUploadUrl(url)', false);
        $response->assertSee('navigator.clipboard.writeText(url)', false);
        $response->assertSee('this.copiedUploadUrl = true', false);
        $response->assertSee('setTimeout(() => {', false);
        $response->assertSee('this.copiedUploadUrl = false', false);
        $response->assertSee('}, 2000)', false);

        // Verify error handling
        $response->assertSee('.catch((error) => {', false);
        $response->assertSee('console.error(\'Failed to copy URL to clipboard:\', error)', false);

        // Verify keyboard handling
        $response->assertSee('handleKeydown(event, url)', false);
        $response->assertSee('event.key === \'Enter\'', false);
        $response->assertSee('event.key === \' \'', false);
        $response->assertSee('event.preventDefault()', false);
    }

    public function test_component_includes_accessibility_features()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com'
        ]);

        $this->setupGoogleDriveEnvironment($user);
        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);

        // Check for screen reader announcement area
        $response->assertSee('x-ref="copyStatus"', false);
        $response->assertSee('aria-live="polite"', false);
        $response->assertSee('aria-atomic="true"', false);
        $response->assertSee('class="sr-only"', false);

        // Check for proper ARIA attributes on button
        $response->assertSee('role="button"', false);
        $response->assertSee('tabindex="0"', false);
        $response->assertSee(':aria-label="copiedUploadUrl', false);
        $response->assertSee(':aria-pressed="copiedUploadUrl"', false);

        // Check for proper ARIA attributes on URL display
        $response->assertSee('role="textbox"', false);
        $response->assertSee('aria-readonly="true"', false);

        // Check for aria-hidden on text spans
        $response->assertSee('aria-hidden="true"', false);
    }

    public function test_component_includes_proper_visual_feedback()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com'
        ]);

        $this->setupGoogleDriveEnvironment($user);
        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);

        // Check for conditional display of button text
        $response->assertSee('<span x-show="!copiedUploadUrl"', false);
        $response->assertSee('<span x-show="copiedUploadUrl"', false);

        // Check for success styling
        $response->assertSee('class="text-green-600"', false);

        // Check for proper button styling classes
        $response->assertSee('border-blue-300', false);
        $response->assertSee('text-blue-700', false);
        $response->assertSee('hover:bg-blue-50', false);
        $response->assertSee('focus:ring-blue-500', false);
    }

    public function test_component_handles_different_user_states()
    {
        // Clear any existing cloud storage settings
        \App\Models\CloudStorageSetting::truncate();
        
        // Test with admin user
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com'
        ]);

        $this->setupGoogleDriveEnvironment($admin);
        $this->actingAs($admin);

        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('copyUploadUrl(', false);

        // Clear settings again for employee test
        \App\Models\CloudStorageSetting::truncate();

        // Test with employee user
        $employee = User::factory()->create([
            'role' => 'employee',
            'username' => 'testemployee',
            'email' => 'employee@example.com'
        ]);

        $this->setupGoogleDriveEnvironment($employee);
        $this->actingAs($employee);

        $response = $this->get('/employee/testemployee/dashboard');
        $response->assertStatus(200);
        $response->assertSee('copyUploadUrl(', false);
    }

    public function test_component_only_renders_when_google_drive_connected()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com'
        ]);

        // Configure Google Drive app but don't connect user
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_id', 'user_id' => null],
            ['value' => 'test_client_id']
        );

        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_secret', 'user_id' => null],
            ['value' => 'test_client_secret']
        );

        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);

        // Should not see the upload URL section when not connected
        $response->assertDontSee('Your upload page', false);
        $response->assertDontSee('Share this URL with clients', false);

        // Should see connection prompt instead
        $response->assertSee('Connect Google Drive', false);
    }

    public function test_component_does_not_render_when_google_drive_not_configured()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com'
        ]);

        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);

        // Should not see the upload URL section when app not configured
        $response->assertDontSee('Your upload page', false);
        $response->assertDontSee('Share this URL with clients', false);

        // Should see configuration prompt instead - check for the admin-specific message
        $response->assertSee('Google Drive app not configured', false);
    }

    public function test_component_includes_proper_error_handling_timeouts()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com'
        ]);

        $this->setupGoogleDriveEnvironment($user);
        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);

        // Check for success timeout (2 seconds)
        $response->assertSee('setTimeout(() => {', false);
        $response->assertSee('this.copiedUploadUrl = false', false);
        $response->assertSee('}, 2000)', false);

        // Check for error timeout (3 seconds)
        $response->assertSee('setTimeout(() => {', false);
        $response->assertSee('}, 3000)', false);
    }

    public function test_component_includes_screen_reader_announcements()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com'
        ]);

        $this->setupGoogleDriveEnvironment($user);
        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);

        // Check for screen reader success announcement (rendered text)
        $response->assertSee('this.$refs.copyStatus.textContent = \'Copied!\'', false);

        // Check for screen reader error announcement (rendered text)
        $response->assertSee('this.$refs.copyStatus.textContent = \'Failed to copy URL\'', false);

        // Check for announcement clearing
        $response->assertSee('this.$refs.copyStatus.textContent = \'\'', false);
    }

    public function test_component_translation_keys_are_used()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com'
        ]);

        $this->setupGoogleDriveEnvironment($user);
        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);

        // Check for rendered translation text (these are the actual English translations)
        $response->assertSee('Copy URL', false);
        $response->assertSee('Copied!', false);
        $response->assertSee('Failed to copy URL', false);
        $response->assertSee('Upload URL for sharing with clients', false);
        $response->assertSee('Copy URL to clipboard', false);
        $response->assertSee('URL copied to clipboard', false);
    }

    public function test_component_maintains_consistent_styling_with_design_system()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com'
        ]);

        $this->setupGoogleDriveEnvironment($user);
        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);

        // Check for consistent button styling
        $response->assertSee('inline-flex items-center px-3 py-1', false);
        $response->assertSee('border border-blue-300 shadow-sm', false);
        $response->assertSee('text-xs font-medium rounded', false);
        $response->assertSee('text-blue-700 bg-white', false);
        $response->assertSee('hover:bg-blue-50', false);
        $response->assertSee('focus:outline-none focus:ring-2', false);
        $response->assertSee('focus:ring-offset-2 focus:ring-blue-500', false);

        // Check for consistent URL display styling
        $response->assertSee('text-sm bg-white px-2 py-1 rounded border', false);
        $response->assertSee('flex-1 mr-2 truncate', false);

        // Check for success text styling
        $response->assertSee('text-green-600', false);
    }

    public function test_component_works_with_different_upload_urls()
    {
        // Clear any existing cloud storage settings
        \App\Models\CloudStorageSetting::truncate();
        
        $user1 = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin1@example.com'
        ]);

        $user2 = User::factory()->create([
            'role' => 'employee',
            'username' => 'employee2',
            'email' => 'employee2@example.com'
        ]);

        $this->setupGoogleDriveEnvironment($user1);
        
        // Test admin dashboard
        $this->actingAs($user1);
        $response1 = $this->get('/admin/dashboard');
        $response1->assertStatus(200);
        $response1->assertSee($user1->upload_url, false);
        $response1->assertSee('copyUploadUrl(\'' . $user1->upload_url . '\')', false);

        // Set up second user with same settings (they should be shared)
        GoogleDriveToken::create([
            'user_id' => $user2->id,
            'access_token' => 'test_access_token_2',
            'refresh_token' => 'test_refresh_token_2',
            'expires_at' => now()->addHour(),
            'token_type' => 'Bearer',
            'scopes' => ['https://www.googleapis.com/auth/drive.file']
        ]);

        // Test employee dashboard
        $this->actingAs($user2);
        $response2 = $this->get('/employee/employee2/dashboard');
        $response2->assertStatus(200);
        $response2->assertSee($user2->upload_url, false);
        $response2->assertSee('copyUploadUrl(\'' . $user2->upload_url . '\')', false);
    }

    public function test_component_integration_with_dashboard_layout()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com'
        ]);

        $this->setupGoogleDriveEnvironment($user);
        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);

        // Check that component is properly integrated in dashboard
        $response->assertSee('Google Drive Connection', false);
        $response->assertSee('p-4 sm:p-8 bg-white shadow sm:rounded-lg', false);

        // Check that Alpine.js data is properly scoped to the component
        $response->assertSee('x-data="{', false);

        // Verify component doesn't interfere with other dashboard elements
        $response->assertSee('Dashboard', false);
        $response->assertSee('Welcome', false);
    }
}