<?php

namespace Tests\Feature\Employee;

use Tests\TestCase;
use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Models\FileUpload;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

class DashboardTest extends TestCase
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
     * Test dashboard shows Google Drive connection when Google Drive is configured.
     * 
     * Requirements: 1.1, 1.2
     */
    public function test_dashboard_shows_google_drive_connection_when_google_drive_configured()
    {
        // Configure Google Drive as the default provider
        Config::set('cloud-storage.default', 'google-drive');
        
        // Create Google Drive token for employee
        GoogleDriveToken::create([
            'user_id' => $this->employee->id,
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/drive'],
        ]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        $response->assertViewIs('employee.dashboard');
        
        // Verify storage provider context is passed to view
        $response->assertViewHas('storageProvider', function ($provider) {
            return $provider['provider'] === 'google-drive' 
                && $provider['requires_user_auth'] === true
                && $provider['display_name'] === 'Google Drive';
        });
        
        // Verify Google Drive connection component is rendered
        $response->assertSee('Google Drive');
    }

    /**
     * Test dashboard shows upload page section when S3 is configured.
     * 
     * Requirements: 1.1, 1.2
     */
    public function test_dashboard_shows_upload_page_section_when_s3_configured()
    {
        // Configure Amazon S3 as the default provider
        Config::set('cloud-storage.default', 'amazon-s3');

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        $response->assertViewIs('employee.dashboard');
        
        // Verify storage provider context is passed to view
        $response->assertViewHas('storageProvider', function ($provider) {
            return $provider['provider'] === 'amazon-s3' 
                && $provider['requires_user_auth'] === false
                && $provider['display_name'] === 'Amazon S3'
                && $provider['auth_type'] === 'api_key';
        });
        
        // Verify upload page section component is rendered (check for actual content)
        $response->assertSee('Your Upload Page');
        $response->assertSee('Amazon S3');
    }

    /**
     * Test dashboard hides cloud storage widget for S3.
     * 
     * Requirements: 2.1, 2.2
     */
    public function test_dashboard_hides_cloud_storage_widget_for_s3()
    {
        // Configure Amazon S3 as the default provider
        Config::set('cloud-storage.default', 'amazon-s3');

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify storage provider context indicates system-level auth
        $response->assertViewHas('storageProvider', function ($provider) {
            return $provider['requires_user_auth'] === false;
        });
        
        // Cloud storage status widget should not be rendered for S3
        // Instead, simplified storage info should be shown
        $response->assertDontSee('Cloud Storage Status');
        $response->assertSee('Cloud Storage Information');
        $response->assertSee('Managed by your administrator');
    }

    /**
     * Test dashboard shows cloud storage widget for Google Drive.
     * 
     * Requirements: 2.1, 2.2
     */
    public function test_dashboard_shows_cloud_storage_widget_for_google_drive()
    {
        // Configure Google Drive as the default provider
        Config::set('cloud-storage.default', 'google-drive');
        
        // Create Google Drive token for employee
        GoogleDriveToken::create([
            'user_id' => $this->employee->id,
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/drive'],
        ]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify storage provider context indicates user-level auth
        $response->assertViewHas('storageProvider', function ($provider) {
            return $provider['requires_user_auth'] === true;
        });
        
        // Cloud storage status widget should be rendered for Google Drive
        // The widget would show cloud storage status information
        $response->assertDontSee('Managed by your administrator');
    }

    /**
     * Test dashboard handles missing provider configuration gracefully.
     * 
     * Requirements: 10.1, 10.2
     */
    public function test_dashboard_handles_missing_provider_configuration_gracefully()
    {
        // Set an invalid provider
        Config::set('cloud-storage.default', 'invalid-provider');

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify default provider context is returned with error flag
        $response->assertViewHas('storageProvider', function ($provider) {
            return $provider['provider'] === 'unknown'
                && $provider['error'] === true
                && $provider['display_name'] === 'Cloud Storage';
        });
        
        // Should show error message directing user to contact admin
        // With error flag, the view should still render but may show fallback content
        // The dashboard should still be accessible
        $response->assertSee('Employee Dashboard');
    }

    /**
     * Test dashboard handles null provider configuration gracefully.
     * 
     * Requirements: 10.1, 10.2
     */
    public function test_dashboard_handles_null_provider_configuration()
    {
        // Set provider to null
        Config::set('cloud-storage.default', null);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify default provider context is returned
        $response->assertViewHas('storageProvider', function ($provider) {
            return $provider['provider'] === 'unknown'
                && $provider['error'] === true;
        });
    }

    /**
     * Test dashboard shows correct UI for Microsoft Teams (OAuth provider).
     * 
     * Requirements: 1.1, 2.1
     */
    public function test_dashboard_shows_correct_ui_for_microsoft_teams()
    {
        // Configure Microsoft Teams as the default provider
        Config::set('cloud-storage.default', 'microsoft-teams');

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify storage provider context indicates OAuth auth
        $response->assertViewHas('storageProvider', function ($provider) {
            return $provider['provider'] === 'microsoft-teams'
                && $provider['requires_user_auth'] === true
                && $provider['auth_type'] === 'oauth'
                && $provider['display_name'] === 'Microsoft Teams';
        });
    }

    /**
     * Test dashboard displays files correctly regardless of provider.
     */
    public function test_dashboard_displays_files_regardless_of_provider()
    {
        // Create some file uploads
        FileUpload::factory()->count(3)->create([
            'company_user_id' => $this->employee->id,
            'storage_provider' => 'google-drive',
        ]);

        // Test with Google Drive
        Config::set('cloud-storage.default', 'google-drive');
        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));
        
        $response->assertStatus(200);
        $response->assertViewHas('files');
        
        // Test with S3
        Config::set('cloud-storage.default', 'amazon-s3');
        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));
        
        $response->assertStatus(200);
        $response->assertViewHas('files');
    }

    /**
     * Test dashboard provider context includes all required fields.
     */
    public function test_dashboard_provider_context_includes_required_fields()
    {
        Config::set('cloud-storage.default', 'google-drive');

        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        
        // Verify all required fields are present
        $response->assertViewHas('storageProvider', function ($provider) {
            return isset($provider['provider'])
                && isset($provider['auth_type'])
                && isset($provider['storage_model'])
                && isset($provider['requires_user_auth'])
                && isset($provider['display_name']);
        });
    }

    /**
     * Test dashboard layout consistency between providers.
     * 
     * Requirements: 1.1, 1.2
     */
    public function test_dashboard_layout_consistency_between_providers()
    {
        // Test Google Drive layout
        Config::set('cloud-storage.default', 'google-drive');
        $googleDriveResponse = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));
        
        $googleDriveResponse->assertStatus(200);
        $googleDriveResponse->assertSee('Employee Dashboard');
        
        // Test S3 layout
        Config::set('cloud-storage.default', 'amazon-s3');
        $s3Response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));
        
        $s3Response->assertStatus(200);
        $s3Response->assertSee('Employee Dashboard');
        
        // Both should have consistent structure
        $googleDriveResponse->assertViewIs('employee.dashboard');
        $s3Response->assertViewIs('employee.dashboard');
    }

    /**
     * Test dashboard shows upload URL for both providers.
     * 
     * Requirements: 1.1, 1.2
     */
    public function test_dashboard_shows_upload_url_for_both_providers()
    {
        // Set upload URL for employee - need to use the full URL format
        $uploadUrl = url('/upload/' . $this->employee->username);
        $this->employee->update(['upload_url' => $uploadUrl]);

        // Test with Google Drive
        Config::set('cloud-storage.default', 'google-drive');
        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));
        
        $response->assertStatus(200);
        $response->assertSee($this->employee->username);
        
        // Test with S3
        Config::set('cloud-storage.default', 'amazon-s3');
        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));
        
        $response->assertStatus(200);
        $response->assertSee($this->employee->username);
    }

    /**
     * Test dashboard handles provider switching correctly.
     * 
     * Requirements: 1.1, 1.2, 2.1, 2.2
     */
    public function test_dashboard_handles_provider_switching()
    {
        // Start with Google Drive
        Config::set('cloud-storage.default', 'google-drive');
        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));
        
        $response->assertStatus(200);
        $response->assertViewHas('storageProvider', function ($provider) {
            return $provider['provider'] === 'google-drive';
        });
        
        // Switch to S3
        Config::set('cloud-storage.default', 'amazon-s3');
        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));
        
        $response->assertStatus(200);
        $response->assertViewHas('storageProvider', function ($provider) {
            return $provider['provider'] === 'amazon-s3';
        });
        
        // Switch back to Google Drive
        Config::set('cloud-storage.default', 'google-drive');
        $response = $this->actingAs($this->employee)
            ->get(route('employee.dashboard', ['username' => $this->employee->username]));
        
        $response->assertStatus(200);
        $response->assertViewHas('storageProvider', function ($provider) {
            return $provider['provider'] === 'google-drive';
        });
    }
}
