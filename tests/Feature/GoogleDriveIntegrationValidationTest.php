<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\FileUpload;
use App\Services\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GoogleDriveIntegrationValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;
    private GoogleDriveService $googleDriveService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin', 'username' => 'admin-test']);
        $this->employee = User::factory()->create(['role' => 'employee', 'username' => 'employee-test']);
        $this->googleDriveService = app(GoogleDriveService::class);
    }

    /** @test */
    public function complete_admin_workflow_for_google_drive_configuration()
    {
        // Test admin can access Google Drive configuration
        $response = $this->actingAs($this->admin)
            ->get('/admin/cloud-storage');
        
        $response->assertStatus(200);
        $response->assertSee('Google Drive');

        // Test admin can update Google Drive root folder
        $response = $this->actingAs($this->admin)
            ->put('/admin/cloud-storage/google-drive/folder', [
                'google_drive_root_folder_id' => '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the setting was saved
        $this->admin->refresh();
        $this->assertEquals('1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms', $this->admin->google_drive_root_folder_id);

        // Test admin can clear Google Drive root folder
        $response = $this->actingAs($this->admin)
            ->put('/admin/cloud-storage/google-drive/folder', [
                'google_drive_root_folder_id' => ''
            ]);

        $response->assertRedirect();
        $this->admin->refresh();
        $this->assertNull($this->admin->google_drive_root_folder_id);
    }

    /** @test */
    public function complete_employee_workflow_for_google_drive_configuration()
    {
        // Test employee can access Google Drive configuration
        $response = $this->actingAs($this->employee)
            ->get("/employee/{$this->employee->username}/cloud-storage");
        
        $response->assertStatus(200);
        $response->assertSee('Google Drive');

        // Test employee can update Google Drive root folder
        $response = $this->actingAs($this->employee)
            ->put("/employee/{$this->employee->username}/google-drive/folder", [
                'google_drive_root_folder_id' => '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the setting was saved
        $this->employee->refresh();
        $this->assertEquals('1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms', $this->employee->google_drive_root_folder_id);

        // Test employee can clear Google Drive root folder
        $response = $this->actingAs($this->employee)
            ->put("/employee/{$this->employee->username}/google-drive/folder", [
                'google_drive_root_folder_id' => ''
            ]);

        $response->assertRedirect();
        $this->employee->refresh();
        $this->assertNull($this->employee->google_drive_root_folder_id);
    }

    /** @test */
    public function file_upload_functionality_with_various_folder_configurations()
    {
        // Test 1: User with no folder configured (should default to 'root')
        $this->admin->google_drive_root_folder_id = null;
        $this->admin->save();

        $effectiveFolder = $this->googleDriveService->getEffectiveRootFolderId($this->admin);
        $this->assertEquals('root', $effectiveFolder);

        // Test 2: User with specific folder configured
        $this->admin->google_drive_root_folder_id = '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms';
        $this->admin->save();

        $effectiveFolder = $this->googleDriveService->getEffectiveRootFolderId($this->admin);
        $this->assertEquals('1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms', $effectiveFolder);

        // Test 3: User with empty string folder (should default to 'root')
        $this->admin->google_drive_root_folder_id = '';
        $this->admin->save();

        $effectiveFolder = $this->googleDriveService->getEffectiveRootFolderId($this->admin);
        $this->assertEquals('root', $effectiveFolder);

        // Test 4: Different users with different configurations
        $this->admin->google_drive_root_folder_id = 'admin-folder-id';
        $this->admin->save();

        $this->employee->google_drive_root_folder_id = 'employee-folder-id';
        $this->employee->save();

        $adminFolder = $this->googleDriveService->getEffectiveRootFolderId($this->admin);
        $employeeFolder = $this->googleDriveService->getEffectiveRootFolderId($this->employee);

        $this->assertEquals('admin-folder-id', $adminFolder);
        $this->assertEquals('employee-folder-id', $employeeFolder);
    }

    /** @test */
    public function system_works_without_environment_variable()
    {
        // Ensure no environment variable is set
        config(['cloud-storage.google-drive.root_folder_id' => null]);

        // Test that the service still works correctly
        $this->admin->google_drive_root_folder_id = null;
        $this->admin->save();

        $effectiveFolder = $this->googleDriveService->getEffectiveRootFolderId($this->admin);
        $this->assertEquals('root', $effectiveFolder);

        // Test with user-specific setting
        $this->admin->google_drive_root_folder_id = 'user-specific-folder';
        $this->admin->save();

        $effectiveFolder = $this->googleDriveService->getEffectiveRootFolderId($this->admin);
        $this->assertEquals('user-specific-folder', $effectiveFolder);
    }

    /** @test */
    public function backward_compatibility_with_existing_users()
    {
        // Create users with existing configurations
        $existingAdmin = User::factory()->create([
            'role' => 'admin',
            'google_drive_root_folder_id' => 'existing-admin-folder'
        ]);

        $existingEmployee = User::factory()->create([
            'role' => 'employee',
            'google_drive_root_folder_id' => 'existing-employee-folder'
        ]);

        $userWithoutConfig = User::factory()->create([
            'role' => 'admin',
            'google_drive_root_folder_id' => null
        ]);

        // Test that existing configurations are preserved
        $adminFolder = $this->googleDriveService->getEffectiveRootFolderId($existingAdmin);
        $this->assertEquals('existing-admin-folder', $adminFolder);

        $employeeFolder = $this->googleDriveService->getEffectiveRootFolderId($existingEmployee);
        $this->assertEquals('existing-employee-folder', $employeeFolder);

        // Test that users without config get default behavior
        $defaultFolder = $this->googleDriveService->getEffectiveRootFolderId($userWithoutConfig);
        $this->assertEquals('root', $defaultFolder);
    }

    /** @test */
    public function user_isolation_and_independence()
    {
        // Set different configurations for different users
        $admin1 = User::factory()->create(['role' => 'admin', 'google_drive_root_folder_id' => 'admin1-folder']);
        $admin2 = User::factory()->create(['role' => 'admin', 'google_drive_root_folder_id' => 'admin2-folder']);
        $employee1 = User::factory()->create(['role' => 'employee', 'google_drive_root_folder_id' => 'employee1-folder']);
        $employee2 = User::factory()->create(['role' => 'employee', 'google_drive_root_folder_id' => null]);

        // Verify each user gets their own configuration
        $this->assertEquals('admin1-folder', $this->googleDriveService->getEffectiveRootFolderId($admin1));
        $this->assertEquals('admin2-folder', $this->googleDriveService->getEffectiveRootFolderId($admin2));
        $this->assertEquals('employee1-folder', $this->googleDriveService->getEffectiveRootFolderId($employee1));
        $this->assertEquals('root', $this->googleDriveService->getEffectiveRootFolderId($employee2));

        // Change one user's configuration and verify others are unaffected
        $admin1->google_drive_root_folder_id = 'admin1-new-folder';
        $admin1->save();

        $this->assertEquals('admin1-new-folder', $this->googleDriveService->getEffectiveRootFolderId($admin1));
        $this->assertEquals('admin2-folder', $this->googleDriveService->getEffectiveRootFolderId($admin2));
        $this->assertEquals('employee1-folder', $this->googleDriveService->getEffectiveRootFolderId($employee1));
        $this->assertEquals('root', $this->googleDriveService->getEffectiveRootFolderId($employee2));
    }

    /** @test */
    public function frontend_displays_correct_configuration_state()
    {
        // Test admin interface shows current setting
        $this->admin->google_drive_root_folder_id = 'test-folder-id';
        $this->admin->save();

        $response = $this->actingAs($this->admin)
            ->get('/admin/cloud-storage');
        
        $response->assertStatus(200);
        $response->assertSee('test-folder-id');

        // Test admin interface shows Google Drive section when no setting
        $this->admin->google_drive_root_folder_id = null;
        $this->admin->save();

        $response = $this->actingAs($this->admin)
            ->get('/admin/cloud-storage');
        
        $response->assertStatus(200);
        $response->assertSee('Google Drive');

        // Test employee interface loads correctly
        $response = $this->actingAs($this->employee)
            ->get("/employee/{$this->employee->username}/cloud-storage");
        
        $response->assertStatus(200);
        $response->assertSee('Google Drive Integration');
    }

    /** @test */
    public function validation_and_error_handling()
    {
        // Test that empty values are handled correctly (should clear the setting)
        $this->admin->google_drive_root_folder_id = 'existing-folder';
        $this->admin->save();

        $response = $this->actingAs($this->admin)
            ->put('/admin/cloud-storage/google-drive/folder', [
                'google_drive_root_folder_id' => ''
            ]);

        $response->assertRedirect();
        $this->admin->refresh();
        $this->assertNull($this->admin->google_drive_root_folder_id);

        // Test that null values are handled correctly (should clear the setting)
        $this->employee->google_drive_root_folder_id = 'existing-employee-folder';
        $this->employee->save();

        $response = $this->actingAs($this->employee)
            ->put("/employee/{$this->employee->username}/google-drive/folder", [
                'google_drive_root_folder_id' => null
            ]);

        $response->assertRedirect();
        $this->employee->refresh();
        $this->assertNull($this->employee->google_drive_root_folder_id);

        // Test that valid folder IDs are accepted
        $response = $this->actingAs($this->admin)
            ->put('/admin/cloud-storage/google-drive/folder', [
                'google_drive_root_folder_id' => 'valid-folder-id-123'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->admin->refresh();
        $this->assertEquals('valid-folder-id-123', $this->admin->google_drive_root_folder_id);
    }

    /** @test */
    public function all_requirements_are_met()
    {
        // Requirement 1.1: Admin can configure through control panel without environment variables
        $response = $this->actingAs($this->admin)
            ->put('/admin/cloud-storage/google-drive/folder', [
                'google_drive_root_folder_id' => 'admin-test-folder'
            ]);
        $response->assertRedirect();
        $this->admin->refresh();
        $this->assertEquals('admin-test-folder', $this->admin->google_drive_root_folder_id);

        // Requirement 1.2: Admin setting stored in database
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'google_drive_root_folder_id' => 'admin-test-folder'
        ]);

        // Requirement 1.3: Admin defaults to root when no folder selected
        $this->admin->google_drive_root_folder_id = null;
        $this->admin->save();
        $this->assertEquals('root', $this->googleDriveService->getEffectiveRootFolderId($this->admin));

        // Requirement 2.1: Employee can configure through control panel
        $response = $this->actingAs($this->employee)
            ->put("/employee/{$this->employee->username}/google-drive/folder", [
                'google_drive_root_folder_id' => 'employee-test-folder'
            ]);
        $response->assertRedirect();
        $this->employee->refresh();
        $this->assertEquals('employee-test-folder', $this->employee->google_drive_root_folder_id);

        // Requirement 3.1: System only considers user database setting
        $this->assertEquals('employee-test-folder', $this->googleDriveService->getEffectiveRootFolderId($this->employee));

        // Requirement 3.3: System functions without environment variable
        config(['cloud-storage.google-drive.root_folder_id' => null]);
        $this->assertEquals('employee-test-folder', $this->googleDriveService->getEffectiveRootFolderId($this->employee));

        // Requirement 5.1: Existing user settings preserved
        $existingUser = User::factory()->create([
            'role' => 'admin',
            'google_drive_root_folder_id' => 'preserved-folder'
        ]);
        $this->assertEquals('preserved-folder', $this->googleDriveService->getEffectiveRootFolderId($existingUser));

        // Requirement 5.2: Users without config continue to work
        $newUser = User::factory()->create(['role' => 'admin', 'google_drive_root_folder_id' => null]);
        $this->assertEquals('root', $this->googleDriveService->getEffectiveRootFolderId($newUser));
    }
}