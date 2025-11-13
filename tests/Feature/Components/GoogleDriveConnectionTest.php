<?php

namespace Tests\Feature\Components;

use Tests\TestCase;
use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Models\CloudStorageSetting;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

/**
 * Test suite for the Google Drive Connection component.
 * 
 * This component manages Google Drive-specific connection functionality
 * and should only render when Google Drive is the configured storage provider.
 */
class GoogleDriveConnectionTest extends TestCase
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
     * Test component does not render when provider is Amazon S3.
     * 
     * Requirements: 5.1, 5.2
     */
    public function test_component_does_not_render_for_amazon_s3()
    {
        // Configure Amazon S3 as the default provider
        Config::set('cloud-storage.default', 'amazon-s3');
        Config::set('cloud-storage.providers.amazon-s3', [
            'display_name' => 'Amazon S3',
            'requires_user_auth' => false,
        ]);
        
        // Configure S3 credentials
        Config::set('filesystems.disks.s3.key', 'test-key');
        Config::set('filesystems.disks.s3.secret', 'test-secret');
        Config::set('filesystems.disks.s3.bucket', 'test-bucket');
        
        // Set upload URL for admin
        $uploadUrl = url('/upload/admin');
        $this->admin->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Verify Google Drive connection component is NOT rendered
        $response->assertDontSee('Google Drive Connection Management');
        $response->assertDontSee('google_drive_connection_management');
        
        // Verify Google Drive-specific elements are NOT present
        $response->assertDontSee('Connect Google Drive');
        $response->assertDontSee('Disconnect');
        $response->assertDontSee('Google Drive is connected');
        $response->assertDontSee('Google Drive is not connected');
        
        // Verify the component returns early without displaying any content
        // The upload page section should be present, but not the Google Drive connection widget
        $response->assertSee('Your Upload Page');
    }

    /**
     * Test component does not render for other system-level storage providers.
     * 
     * Requirements: 5.1, 5.2
     */
    public function test_component_does_not_render_for_system_level_providers()
    {
        // Configure a hypothetical system-level provider
        Config::set('cloud-storage.default', 'dropbox');
        Config::set('cloud-storage.providers.dropbox', [
            'display_name' => 'Dropbox',
            'requires_user_auth' => false,
        ]);
        
        // Set upload URL for admin
        $uploadUrl = url('/upload/admin');
        $this->admin->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Verify Google Drive connection component is NOT rendered
        $response->assertDontSee('Google Drive Connection Management');
        $response->assertDontSee('Connect Google Drive');
        $response->assertDontSee('Disconnect');
    }

    /**
     * Test component returns early without any output for non-Google Drive providers.
     * 
     * Requirements: 5.1, 5.2
     */
    public function test_component_returns_early_for_non_google_drive_providers()
    {
        // Configure Amazon S3
        Config::set('cloud-storage.default', 'amazon-s3');
        Config::set('cloud-storage.providers.amazon-s3', [
            'display_name' => 'Amazon S3',
            'requires_user_auth' => false,
        ]);
        
        // Configure S3 credentials
        Config::set('filesystems.disks.s3.key', 'test-key');
        Config::set('filesystems.disks.s3.secret', 'test-secret');
        Config::set('filesystems.disks.s3.bucket', 'test-bucket');
        
        // Set upload URL for employee
        $uploadUrl = url('/upload/' . $this->employee->username);
        $this->employee->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify no Google Drive connection management UI is present
        $response->assertDontSee('Google Drive Connection Management');
        $response->assertDontSee('google_drive_connection_management');
        
        // Verify no Google Drive status messages
        $response->assertDontSee('Google Drive is connected');
        $response->assertDontSee('Google Drive is not connected');
        $response->assertDontSee('Google Drive app is not configured');
        
        // Verify no Google Drive action buttons
        $response->assertDontSee('Connect Google Drive');
        $response->assertDontSee('admin.cloud-storage.google-drive.connect');
        $response->assertDontSee('employee.google-drive.connect');
    }

    /**
     * Test component renders when provider is Google Drive.
     * 
     * Requirements: 5.3, 5.4, 5.5, 5.7
     */
    public function test_component_renders_for_google_drive_provider()
    {
        // Configure Google Drive as the default provider
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.providers.google-drive', [
            'display_name' => 'Google Drive',
            'requires_user_auth' => true,
        ]);
        
        // Configure Google Drive credentials
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_id', 'user_id' => null],
            ['value' => 'test-client-id']
        );
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_secret', 'user_id' => null],
            ['value' => 'test-client-secret']
        );
        
        // Set upload URL for admin
        $uploadUrl = url('/upload/admin');
        $this->admin->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Verify Google Drive connection component IS rendered
        $response->assertSee('Google Drive Connection Management');
        $response->assertSee('google_drive_connection_management');
        
        // Verify component displays connection management UI
        $response->assertSeeInOrder([
            'Google Drive Connection Management',
            'Google Drive is not connected',
        ]);
    }

    /**
     * Test component displays connection management UI for Google Drive.
     * 
     * Requirements: 5.3, 5.4, 5.5, 5.7
     */
    public function test_component_displays_connection_management_ui()
    {
        // Configure Google Drive as the default provider
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.providers.google-drive', [
            'display_name' => 'Google Drive',
            'requires_user_auth' => true,
        ]);
        
        // Configure Google Drive credentials
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_id', 'user_id' => null],
            ['value' => 'test-client-id']
        );
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_secret', 'user_id' => null],
            ['value' => 'test-client-secret']
        );
        
        // Set upload URL for admin
        $uploadUrl = url('/upload/admin');
        $this->admin->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Verify connection management elements are present
        $response->assertSee('Google Drive Connection Management');
        
        // Verify connect button is present (user not connected)
        $response->assertSee('Connect Google Drive');
        $response->assertSee('connect_google_drive');
        
        // Verify appropriate styling for connection status
        $response->assertSee('bg-yellow-50 border border-yellow-200');
        
        // Verify warning icon is present
        $response->assertSee('text-yellow-500');
    }

    /**
     * Test component shows appropriate status messages when not connected.
     * 
     * Requirements: 5.3, 5.6
     */
    public function test_component_shows_not_connected_status_message()
    {
        // Configure Google Drive as the default provider
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.providers.google-drive', [
            'display_name' => 'Google Drive',
            'requires_user_auth' => true,
        ]);
        
        // Configure Google Drive credentials
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_id', 'user_id' => null],
            ['value' => 'test-client-id']
        );
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_secret', 'user_id' => null],
            ['value' => 'test-client-secret']
        );
        
        // Set upload URL for admin (no Google Drive token)
        $uploadUrl = url('/upload/admin');
        $this->admin->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Verify not connected status message
        $response->assertSee('Google Drive is not connected');
        $response->assertSee('google_drive_not_connected');
        
        // Verify instruction message
        $response->assertSee('Connect your Google Drive to receive client uploads');
        $response->assertSee('connect_drive_to_receive_uploads');
        
        // Verify connect button is present
        $response->assertSee('Connect Google Drive');
    }

    /**
     * Test component shows connected status when user has Google Drive token.
     * 
     * Requirements: 5.4, 5.5, 5.7
     */
    public function test_component_shows_connected_status_with_token()
    {
        // Configure Google Drive as the default provider
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.providers.google-drive', [
            'display_name' => 'Google Drive',
            'requires_user_auth' => true,
        ]);
        
        // Configure Google Drive credentials
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_id', 'user_id' => null],
            ['value' => 'test-client-id']
        );
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_secret', 'user_id' => null],
            ['value' => 'test-client-secret']
        );
        
        // Create Google Drive token for admin
        GoogleDriveToken::factory()->create([
            'user_id' => $this->admin->id,
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_at' => now()->addHour(),
        ]);
        
        // Set upload URL for admin
        $uploadUrl = url('/upload/admin');
        $this->admin->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Verify connected status message
        $response->assertSee('Google Drive is connected');
        $response->assertSee('google_drive_connected');
        
        // Verify success message
        $response->assertSee('Client uploads will go to your Google Drive');
        $response->assertSee('client_uploads_will_go_to_your_drive');
        
        // Verify disconnect button is present
        $response->assertSee('Disconnect');
        $response->assertSee('disconnect');
        
        // Verify success styling
        $response->assertSee('bg-green-50 border border-green-200');
        $response->assertSee('text-green-500');
    }

    /**
     * Test component shows disconnect button when connected.
     * 
     * Requirements: 5.4, 5.5, 5.7
     */
    public function test_component_shows_disconnect_button_when_connected()
    {
        // Configure Google Drive as the default provider
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.providers.google-drive', [
            'display_name' => 'Google Drive',
            'requires_user_auth' => true,
        ]);
        
        // Configure Google Drive credentials
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_id', 'user_id' => null],
            ['value' => 'test-client-id']
        );
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_secret', 'user_id' => null],
            ['value' => 'test-client-secret']
        );
        
        // Create Google Drive token for admin
        GoogleDriveToken::factory()->create([
            'user_id' => $this->admin->id,
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_at' => now()->addHour(),
        ]);
        
        // Set upload URL for admin
        $uploadUrl = url('/upload/admin');
        $this->admin->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Verify disconnect button is present
        $response->assertSee('Disconnect');
        
        // Verify disconnect form action
        $response->assertSee('admin.cloud-storage.google-drive.disconnect');
        
        // Verify button styling
        $response->assertSee('border-red-300');
        $response->assertSee('text-red-700');
        $response->assertSee('bg-white');
        $response->assertSee('hover:bg-red-50');
    }

    /**
     * Test component shows configuration prompt when Google Drive is not configured.
     * 
     * Requirements: 5.6, 5.7
     */
    public function test_component_shows_configuration_prompt_when_not_configured()
    {
        // Configure Google Drive as the default provider but without credentials
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.providers.google-drive', [
            'display_name' => 'Google Drive',
            'requires_user_auth' => true,
        ]);
        
        // Do NOT configure Google Drive credentials
        // This simulates the app not being configured
        
        // Set upload URL for admin
        $uploadUrl = url('/upload/admin');
        $this->admin->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Verify configuration prompt for admin
        $response->assertSee('Google Drive app is not configured');
        $response->assertSee('google_drive_app_not_configured');
        
        // Verify instruction message
        $response->assertSee('Configure your Google Drive app first');
        $response->assertSee('configure_google_drive_app_first');
        
        // Verify configure button is present for admin
        $response->assertSee('Configure Cloud Storage');
        $response->assertSee('configure_cloud_storage');
        
        // Verify error styling
        $response->assertSee('bg-red-50 border border-red-200');
        $response->assertSee('text-red-500');
    }

    /**
     * Test component shows different message for employees when not configured.
     * 
     * Requirements: 5.6, 5.7
     */
    public function test_component_shows_employee_message_when_not_configured()
    {
        // Configure Google Drive as the default provider but without credentials
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.providers.google-drive', [
            'display_name' => 'Google Drive',
            'requires_user_auth' => true,
        ]);
        
        // Do NOT configure Google Drive credentials
        
        // Set upload URL for employee
        $uploadUrl = url('/upload/' . $this->employee->username);
        $this->employee->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify employee-specific message
        $response->assertSee('Google Drive is not configured');
        $response->assertSee('google_drive_not_configured');
        
        // Verify contact admin message
        $response->assertSee('Contact your administrator to configure Google Drive');
        $response->assertSee('contact_admin_to_configure_google_drive');
        
        // Verify NO configure button for employee
        $response->assertDontSee('Configure Cloud Storage');
    }

    /**
     * Test component maintains all existing Google Drive functionality.
     * 
     * Requirements: 5.7
     */
    public function test_component_maintains_existing_google_drive_functionality()
    {
        // Configure Google Drive as the default provider
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.providers.google-drive', [
            'display_name' => 'Google Drive',
            'requires_user_auth' => true,
        ]);
        
        // Configure Google Drive credentials
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_id', 'user_id' => null],
            ['value' => 'test-client-id']
        );
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_secret', 'user_id' => null],
            ['value' => 'test-client-secret']
        );
        
        // Set upload URL for admin
        $uploadUrl = url('/upload/admin');
        $this->admin->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Verify all key Google Drive functionality elements are present
        
        // 1. Connection management section
        $response->assertSee('Google Drive Connection Management');
        
        // 2. Status indicators (icons)
        $response->assertSee('text-yellow-500'); // Warning icon for not connected
        
        // 3. Action buttons
        $response->assertSee('Connect Google Drive');
        
        // 4. Form elements with CSRF protection
        $response->assertSee('csrf');
        
        // 5. Proper routing
        $response->assertSee('admin.cloud-storage.google-drive.connect');
        
        // 6. Accessibility features
        $response->assertSee('role=', false);
        $response->assertSee('aria-', false);
    }

    /**
     * Test component works correctly for employee users with Google Drive.
     * 
     * Requirements: 5.3, 5.4, 5.5, 5.7
     */
    public function test_component_works_for_employee_users()
    {
        // Configure Google Drive as the default provider
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.providers.google-drive', [
            'display_name' => 'Google Drive',
            'requires_user_auth' => true,
        ]);
        
        // Configure Google Drive credentials
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_id', 'user_id' => null],
            ['value' => 'test-client-id']
        );
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'google-drive', 'key' => 'client_secret', 'user_id' => null],
            ['value' => 'test-client-secret']
        );
        
        // Create Google Drive token for employee
        GoogleDriveToken::factory()->create([
            'user_id' => $this->employee->id,
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_at' => now()->addHour(),
        ]);
        
        // Set upload URL for employee
        $uploadUrl = url('/upload/' . $this->employee->username);
        $this->employee->update(['upload_url' => $uploadUrl]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify component renders for employee
        $response->assertSee('Google Drive Connection Management');
        
        // Verify connected status
        $response->assertSee('Google Drive is connected');
        
        // Verify employee-specific disconnect route
        $response->assertSee('employee.google-drive.disconnect');
        $response->assertSee($this->employee->username);
    }
}
