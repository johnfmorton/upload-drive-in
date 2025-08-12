<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Models\FileUpload;
use App\Services\GoogleDriveService;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Mockery;

class GoogleDriveDefaultBehaviorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;
    private GoogleDriveService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'google_drive_root_folder_id' => null // No folder configured
        ]);

        $this->employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $this->admin->id,
            'google_drive_root_folder_id' => null // No folder configured
        ]);

        // Create Google Drive tokens
        GoogleDriveToken::create([
            'user_id' => $this->admin->id,
            'access_token' => 'admin_access_token',
            'refresh_token' => 'admin_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/drive']
        ]);

        GoogleDriveToken::create([
            'user_id' => $this->employee->id,
            'access_token' => 'employee_access_token',
            'refresh_token' => 'employee_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/drive']
        ]);

        $this->service = new GoogleDriveService();
    }

    /**
     * Test admin with no folder configured defaults to Google Drive root.
     */
    public function test_admin_with_no_folder_defaults_to_root()
    {
        $this->assertNull($this->admin->google_drive_root_folder_id);
        
        $effectiveFolder = $this->service->getEffectiveRootFolderId($this->admin);
        
        $this->assertEquals('root', $effectiveFolder);
    }

    /**
     * Test employee with no folder configured defaults to Google Drive root.
     */
    public function test_employee_with_no_folder_defaults_to_root()
    {
        $this->assertNull($this->employee->google_drive_root_folder_id);
        
        $effectiveFolder = $this->service->getEffectiveRootFolderId($this->employee);
        
        $this->assertEquals('root', $effectiveFolder);
    }

    /**
     * Test file upload uses Google Drive root when no folder configured.
     */
    public function test_file_upload_uses_root_when_no_folder_configured()
    {
        Storage::fake('public');
        Storage::disk('public')->put('test-upload.txt', 'test content');

        // Mock the Google Drive service to verify root folder is used
        $mockService = Mockery::mock(GoogleDriveService::class)->makePartial();
        
        // Mock the getDriveService method
        $mockDriveService = Mockery::mock(\Google\Service\Drive::class);
        $mockFilesResource = Mockery::mock();
        $mockDriveService->files = $mockFilesResource;
        
        $mockService->shouldReceive('getDriveService')
            ->andReturn($mockDriveService);

        // Expect folder search in 'root' directory
        $mockFilesList = Mockery::mock();
        $mockFilesList->shouldReceive('getFiles')->andReturn([]);
        
        $mockFilesResource->shouldReceive('listFiles')
            ->with(Mockery::on(function ($params) {
                return str_contains($params['q'], "'root' in parents");
            }))
            ->andReturn($mockFilesList);

        // Mock folder creation in root
        $mockCreatedFolder = Mockery::mock(\Google\Service\Drive\DriveFile::class);
        $mockCreatedFolder->shouldReceive('getId')->andReturn('new-client-folder-id');
        
        $mockFilesResource->shouldReceive('create')
            ->with(Mockery::on(function ($folderMetadata) {
                return $folderMetadata->getParents() === ['root'];
            }), ['fields' => 'id'])
            ->andReturn($mockCreatedFolder);

        // Mock file upload
        $mockUploadedFile = Mockery::mock(\Google\Service\Drive\DriveFile::class);
        $mockUploadedFile->shouldReceive('getId')->andReturn('uploaded-file-id');
        
        $mockFilesResource->shouldReceive('create')
            ->with(Mockery::any(), Mockery::on(function ($options) {
                return isset($options['data']) && isset($options['mimeType']);
            }))
            ->andReturn($mockUploadedFile);

        $result = $mockService->uploadFileForUser(
            $this->employee,
            'test-upload.txt',
            'client@example.com',
            'test-upload.txt',
            'text/plain'
        );

        $this->assertEquals('uploaded-file-id', $result);
    }

    /**
     * Test system works correctly when environment variable is not set.
     */
    public function test_system_works_without_environment_variable()
    {
        // Ensure no environment variable is set
        config(['services.google.root_folder_id' => null]);
        
        // Service should still work and return 'root'
        $rootFolder = $this->service->getRootFolderId();
        $this->assertEquals('root', $rootFolder);
        
        // Effective folder should still work for users
        $effectiveFolder = $this->service->getEffectiveRootFolderId($this->admin);
        $this->assertEquals('root', $effectiveFolder);
    }

    /**
     * Test multiple users with no configuration all default to root.
     */
    public function test_multiple_users_with_no_config_default_to_root()
    {
        $admin2 = User::factory()->create([
            'role' => UserRole::ADMIN,
            'google_drive_root_folder_id' => null
        ]);

        $employee2 = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $this->admin->id,
            'google_drive_root_folder_id' => null
        ]);

        $users = [$this->admin, $this->employee, $admin2, $employee2];

        foreach ($users as $user) {
            $effectiveFolder = $this->service->getEffectiveRootFolderId($user);
            $this->assertEquals('root', $effectiveFolder, "User {$user->id} should default to root");
        }
    }

    /**
     * Test mixed configuration scenario.
     */
    public function test_mixed_configuration_scenario()
    {
        // Admin has custom folder
        $this->admin->update(['google_drive_root_folder_id' => 'admin-custom-folder']);
        
        // Employee has no folder (should default to root)
        $this->employee->update(['google_drive_root_folder_id' => null]);

        $adminFolder = $this->service->getEffectiveRootFolderId($this->admin);
        $employeeFolder = $this->service->getEffectiveRootFolderId($this->employee);

        $this->assertEquals('admin-custom-folder', $adminFolder);
        $this->assertEquals('root', $employeeFolder);
    }

    /**
     * Test default behavior is consistent across service methods.
     */
    public function test_default_behavior_consistent_across_methods()
    {
        // All methods should consistently use 'root' for users with no configuration
        $rootFromService = $this->service->getRootFolderId();
        $effectiveFromAdmin = $this->service->getEffectiveRootFolderId($this->admin);
        $effectiveFromEmployee = $this->service->getEffectiveRootFolderId($this->employee);

        $this->assertEquals('root', $rootFromService);
        $this->assertEquals('root', $effectiveFromAdmin);
        $this->assertEquals('root', $effectiveFromEmployee);
    }

    /**
     * Test backward compatibility with existing users.
     */
    public function test_backward_compatibility_with_existing_users()
    {
        // Simulate existing users who might have had different configurations
        $existingUser = User::factory()->create([
            'role' => UserRole::ADMIN,
            'google_drive_root_folder_id' => null, // Existing user with no setting
            'created_at' => Carbon::now()->subMonths(6) // Created before the change
        ]);

        GoogleDriveToken::create([
            'user_id' => $existingUser->id,
            'access_token' => 'existing_access_token',
            'refresh_token' => 'existing_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/drive']
        ]);

        // Should work seamlessly with default behavior
        $effectiveFolder = $this->service->getEffectiveRootFolderId($existingUser);
        $this->assertEquals('root', $effectiveFolder);
    }

    /**
     * Test error handling when no configuration exists.
     */
    public function test_error_handling_with_no_configuration()
    {
        // Even with no configuration, methods should not throw exceptions
        $this->assertDoesNotThrow(function () {
            $this->service->getRootFolderId();
            $this->service->getEffectiveRootFolderId($this->admin);
            $this->service->getEffectiveRootFolderId($this->employee);
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper method to assert no exceptions are thrown.
     */
    private function assertDoesNotThrow(callable $callback): void
    {
        try {
            $callback();
            $this->assertTrue(true); // If we get here, no exception was thrown
        } catch (\Throwable $e) {
            $this->fail("Expected no exception to be thrown, but got: " . $e->getMessage());
        }
    }
}