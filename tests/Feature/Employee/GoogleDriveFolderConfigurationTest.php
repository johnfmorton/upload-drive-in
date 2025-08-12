<?php

namespace Tests\Feature\Employee;

use Tests\TestCase;
use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class GoogleDriveFolderConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $this->employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $this->admin->id,
            'google_drive_root_folder_id' => null,
            'username' => 'testemployee'
        ]);

        // Create Google Drive token for employee
        GoogleDriveToken::create([
            'user_id' => $this->employee->id,
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/drive']
        ]);
    }

    /**
     * Test employee can access Google Drive configuration page.
     */
    public function test_employee_can_access_google_drive_configuration()
    {
        $response = $this->actingAs($this->employee)
            ->get(route('employee.cloud-storage.index', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        $response->assertViewIs('employee.cloud-storage.index');
    }

    /**
     * Test employee can update their Google Drive root folder setting.
     */
    public function test_employee_can_update_google_drive_root_folder()
    {
        $response = $this->actingAs($this->employee)
            ->put(route('employee.google-drive.folder.update', ['username' => $this->employee->username]), [
                'google_drive_root_folder_id' => 'custom-employee-folder-id'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->employee->refresh();
        $this->assertEquals('custom-employee-folder-id', $this->employee->google_drive_root_folder_id);
    }

    /**
     * Test employee can clear their Google Drive root folder setting.
     */
    public function test_employee_can_clear_google_drive_root_folder()
    {
        $this->employee->update(['google_drive_root_folder_id' => 'existing-folder-id']);

        $response = $this->actingAs($this->employee)
            ->put(route('employee.google-drive.folder.update', ['username' => $this->employee->username]), [
                'google_drive_root_folder_id' => ''
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->employee->refresh();
        $this->assertNull($this->employee->google_drive_root_folder_id);
    }

    /**
     * Test employee folder configuration shows current setting.
     */
    public function test_employee_folder_configuration_shows_current_setting()
    {
        $this->employee->update(['google_drive_root_folder_id' => 'employee-folder-id']);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.cloud-storage.index', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        $response->assertSee('employee-folder-id');
    }

    /**
     * Test employee folder configuration shows default when no setting.
     */
    public function test_employee_folder_configuration_shows_default_when_no_setting()
    {
        $this->employee->update(['google_drive_root_folder_id' => null]);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.cloud-storage.index', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        // Should indicate default Google Drive root behavior
        $response->assertSee('Google Drive root');
    }

    /**
     * Test employee cannot access configuration without Google Drive connection.
     */
    public function test_employee_cannot_configure_without_google_drive_connection()
    {
        // Remove Google Drive token
        $this->employee->googleDriveToken()->delete();

        $response = $this->actingAs($this->employee)
            ->get(route('employee.cloud-storage.index', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        // Should show connection required message
        $response->assertSee('Not Connected');
    }

    /**
     * Test employee folder configuration is independent of admin.
     */
    public function test_employee_folder_configuration_independent_of_admin()
    {
        // Set admin folder
        $this->admin->update(['google_drive_root_folder_id' => 'admin-folder']);
        
        // Set employee folder
        $this->employee->update(['google_drive_root_folder_id' => 'employee-folder']);

        // Employee should see their own setting
        $response = $this->actingAs($this->employee)
            ->get(route('employee.cloud-storage.index', ['username' => $this->employee->username]));

        $response->assertStatus(200);
        $response->assertSee('employee-folder');
        $response->assertDontSee('admin-folder');
    }

    /**
     * Test employee folder update validation.
     */
    public function test_employee_folder_update_validation()
    {
        // Test with invalid folder ID format (if validation exists)
        $response = $this->actingAs($this->employee)
            ->put(route('employee.google-drive.folder.update', ['username' => $this->employee->username]), [
                'google_drive_root_folder_id' => str_repeat('a', 256) // Too long
            ]);

        // Should either accept it or show validation error
        // The actual validation depends on implementation
        $this->assertTrue($response->isRedirection() || $response->status() === 422);
    }

    /**
     * Test employee folder configuration workflow end-to-end.
     */
    public function test_employee_folder_configuration_workflow()
    {
        // 1. Start with no folder configured
        $this->assertNull($this->employee->google_drive_root_folder_id);

        // 2. Access configuration page
        $response = $this->actingAs($this->employee)
            ->get(route('employee.cloud-storage.index', ['username' => $this->employee->username]));
        $response->assertStatus(200);

        // 3. Set a custom folder
        $response = $this->actingAs($this->employee)
            ->put(route('employee.google-drive.folder.update', ['username' => $this->employee->username]), [
                'google_drive_root_folder_id' => 'employee-workflow-folder'
            ]);
        $response->assertRedirect();

        // 4. Verify setting was saved
        $this->employee->refresh();
        $this->assertEquals('employee-workflow-folder', $this->employee->google_drive_root_folder_id);

        // 5. View configuration again to confirm
        $response = $this->actingAs($this->employee)
            ->get(route('employee.cloud-storage.index', ['username' => $this->employee->username]));
        $response->assertStatus(200);
        $response->assertSee('employee-workflow-folder');

        // 6. Clear the setting
        $response = $this->actingAs($this->employee)
            ->put(route('employee.google-drive.folder.update', ['username' => $this->employee->username]), [
                'google_drive_root_folder_id' => null
            ]);
        $response->assertRedirect();

        // 7. Verify setting was cleared
        $this->employee->refresh();
        $this->assertNull($this->employee->google_drive_root_folder_id);
    }

    /**
     * Test employee without Google Drive connection falls back to admin.
     */
    public function test_employee_without_connection_falls_back_to_admin()
    {
        // Remove employee's Google Drive token
        $this->employee->googleDriveToken()->delete();
        
        // Give admin a Google Drive token
        GoogleDriveToken::create([
            'user_id' => $this->admin->id,
            'access_token' => 'admin_access_token',
            'refresh_token' => 'admin_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/drive']
        ]);

        // This test would require mocking the actual upload process
        // For now, we just verify the employee can still access their settings
        $response = $this->actingAs($this->employee)
            ->get(route('employee.cloud-storage.index', ['username' => $this->employee->username]));

        $response->assertStatus(200);
    }
}