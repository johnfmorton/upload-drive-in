<?php

namespace Tests\Feature\Admin;

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

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
            'username' => 'testadmin',
        ]);
    }

    /**
     * Test admin dashboard displays "Your Upload Page" title with Amazon S3 configured.
     * 
     * Requirements: 1.1, 2.1, 3.1, 3.2, 4.1, 4.2, 4.3, 5.1, 5.2
     */
    public function test_admin_dashboard_with_amazon_s3_configured()
    {
        // Configure Amazon S3 as the default provider
        Config::set('cloud-storage.default', 'amazon-s3');
        Config::set('filesystems.disks.s3.key', 'test-key');
        Config::set('filesystems.disks.s3.secret', 'test-secret');
        Config::set('filesystems.disks.s3.bucket', 'test-bucket');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        
        // Requirement 1.1: Verify "Your Upload Page" title is displayed (not "Google Drive Connection")
        $response->assertSee('Your Upload Page');
        
        // Requirement 2.1: Verify cloud icon with "Cloud Storage" label is displayed
        $response->assertSee('Cloud Storage');
        
        // Requirement 3.1, 3.2: Verify S3 info message about organizational storage
        $response->assertSee('Amazon S3', false);
        
        // Requirement 5.1, 5.2: Verify Google Drive connection management widget is NOT displayed for S3
        // The google-drive-connection component should not render at all when S3 is configured
        $response->assertDontSee('Google Drive Connection Management');
        
        // Verify storage provider data is passed to view
        $response->assertViewHas('storageProvider', function ($provider) {
            return $provider['provider'] === 'amazon-s3' 
                && $provider['requires_user_auth'] === false
                && $provider['display_name'] === 'Amazon S3'
                && $provider['is_configured'] === true;
        });
    }

    /**
     * Test admin dashboard with Google Drive configured and connected.
     * 
     * Requirements: 1.3, 2.2, 4.1, 4.2, 4.3, 5.3, 5.4, 5.5
     */
    public function test_admin_dashboard_with_google_drive_configured_and_connected()
    {
        // Configure Google Drive as the default provider
        Config::set('cloud-storage.default', 'google-drive');
        
        // Create Google Drive token for admin (connected state)
        GoogleDriveToken::create([
            'user_id' => $this->admin->id,
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/drive'],
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        
        // Requirement 1.3: Verify "Your Upload Page" title is displayed
        $response->assertSee('Your Upload Page');
        
        // Requirement 2.2: Verify Google Drive icon and label are displayed
        $response->assertSee('Google Drive');
        
        // Requirement 5.3, 5.4, 5.5: Verify Google Drive connection status and disconnect button
        $response->assertSee('Google Drive is connected');
        $response->assertSee('Disconnect');
        $response->assertDontSee('Connect Google Drive');
        
        // Verify Google Drive Connection Management section is present
        $response->assertSee('Google Drive Connection Management');
        
        // Verify storage provider data is passed to view
        $response->assertViewHas('storageProvider', function ($provider) {
            return $provider['provider'] === 'google-drive' 
                && $provider['requires_user_auth'] === true
                && $provider['display_name'] === 'Google Drive'
                && $provider['is_configured'] === true;
        });
    }

    /**
     * Test admin dashboard with Google Drive configured but not connected.
     * 
     * Requirements: 1.3, 2.2, 5.3, 5.6
     */
    public function test_admin_dashboard_with_google_drive_configured_but_not_connected()
    {
        // Configure Google Drive as the default provider with proper credentials
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.providers.google-drive.client_id', 'test-client-id');
        Config::set('cloud-storage.providers.google-drive.client_secret', 'test-client-secret');
        
        // Do NOT create a Google Drive token (not connected state)

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        
        // Requirement 1.3: Verify "Your Upload Page" title is displayed
        $response->assertSee('Your Upload Page');
        
        // Requirement 5.3: Verify connect button is present
        $response->assertSee('Connect Google Drive');
        $response->assertDontSee('Disconnect');
        
        // Requirement 5.6: Verify appropriate connection requirement messaging
        $response->assertSee('Google Drive is not connected');
        
        // Requirement 2.2: Verify Google Drive icon and label are displayed
        $response->assertSee('Google Drive');
        
        // Verify Google Drive Connection Management section is present
        $response->assertSee('Google Drive Connection Management');
        
        // Verify storage provider data is passed to view
        $response->assertViewHas('storageProvider', function ($provider) {
            return $provider['provider'] === 'google-drive' 
                && $provider['requires_user_auth'] === true
                && $provider['display_name'] === 'Google Drive';
        });
    }

    /**
     * Test dashboard consistency between admin and employee dashboards.
     * 
     * Requirements: 1.4, 2.4
     */
    public function test_dashboard_consistency_between_admin_and_employee()
    {
        // Create an employee user
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee@example.com',
            'owner_id' => $this->admin->id,
            'username' => 'testemployee',
        ]);

        // Test with Amazon S3 configuration
        Config::set('cloud-storage.default', 'amazon-s3');
        Config::set('filesystems.disks.s3.key', 'test-key');
        Config::set('filesystems.disks.s3.secret', 'test-secret');
        Config::set('filesystems.disks.s3.bucket', 'test-bucket');
        
        $adminS3Response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));
        
        $employeeS3Response = $this->actingAs($employee)
            ->get(route('employee.dashboard', ['username' => $employee->username]));

        // Requirement 1.4, 2.4: Verify both dashboards show same structure for S3
        $adminS3Response->assertStatus(200);
        $employeeS3Response->assertStatus(200);
        
        // Both should show "Your Upload Page" title
        $adminS3Response->assertSee('Your Upload Page');
        $employeeS3Response->assertSee('Your Upload Page');
        
        // Both should show "Cloud Storage" label
        $adminS3Response->assertSee('Cloud Storage');
        $employeeS3Response->assertSee('Cloud Storage');
        
        // Both should NOT show Google Drive connection management widget for S3
        $adminS3Response->assertDontSee('Google Drive Connection Management');
        $employeeS3Response->assertDontSee('Google Drive Connection Management');
        
        // Test with Google Drive configuration
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.providers.google-drive.client_id', 'test-client-id');
        Config::set('cloud-storage.providers.google-drive.client_secret', 'test-client-secret');
        
        // Create Google Drive tokens for both users
        GoogleDriveToken::create([
            'user_id' => $this->admin->id,
            'access_token' => 'admin_access_token',
            'refresh_token' => 'admin_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/drive'],
        ]);
        
        GoogleDriveToken::create([
            'user_id' => $employee->id,
            'access_token' => 'employee_access_token',
            'refresh_token' => 'employee_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/drive'],
        ]);
        
        $adminGDResponse = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));
        
        $employeeGDResponse = $this->actingAs($employee)
            ->get(route('employee.dashboard', ['username' => $employee->username]));

        // Requirement 1.4, 2.4: Verify both dashboards show same structure for Google Drive
        $adminGDResponse->assertStatus(200);
        $employeeGDResponse->assertStatus(200);
        
        // Both should show "Your Upload Page" title
        $adminGDResponse->assertSee('Your Upload Page');
        $employeeGDResponse->assertSee('Your Upload Page');
        
        // Both should show "Google Drive" label
        $adminGDResponse->assertSee('Google Drive');
        $employeeGDResponse->assertSee('Google Drive');
        
        // Both should show Google Drive connection status
        $adminGDResponse->assertSee('Google Drive is connected');
        $employeeGDResponse->assertSee('Google Drive is connected');
        
        // Both should show disconnect button
        $adminGDResponse->assertSee('Disconnect');
        $employeeGDResponse->assertSee('Disconnect');
        
        // Both should show Google Drive Connection Management section
        $adminGDResponse->assertSee('Google Drive Connection Management');
        $employeeGDResponse->assertSee('Google Drive Connection Management');
    }
}
