<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\FileUpload;
use App\Models\User;
use App\Models\ClientUserRelationship;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FileUploadPermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;
    private User $client1;
    private User $client2;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->client1 = User::factory()->create(['role' => UserRole::CLIENT]);
        $this->client2 = User::factory()->create(['role' => UserRole::CLIENT]);
    }

    /** @test */
    public function admin_can_access_all_files()
    {
        $files = [
            FileUpload::factory()->create(['client_user_id' => $this->client1->id]),
            FileUpload::factory()->create(['client_user_id' => $this->client2->id]),
            FileUpload::factory()->create(['uploaded_by_user_id' => $this->employee->id]),
            FileUpload::factory()->create(['client_user_id' => null]) // System file
        ];

        foreach ($files as $file) {
            $this->assertTrue($file->canBeAccessedBy($this->admin), 
                "Admin should be able to access file {$file->id}");
        }
    }

    /** @test */
    public function client_can_only_access_own_files()
    {
        $ownFile = FileUpload::factory()->create(['client_user_id' => $this->client1->id]);
        $otherFile = FileUpload::factory()->create(['client_user_id' => $this->client2->id]);
        $systemFile = FileUpload::factory()->create(['client_user_id' => null]);

        $this->assertTrue($ownFile->canBeAccessedBy($this->client1));
        $this->assertFalse($otherFile->canBeAccessedBy($this->client1));
        $this->assertFalse($systemFile->canBeAccessedBy($this->client1));
    }

    /** @test */
    public function employee_can_access_managed_client_files()
    {
        // Create relationship between employee and client1
        ClientUserRelationship::create([
            'company_user_id' => $this->employee->id,
            'client_user_id' => $this->client1->id,
            'is_primary' => true
        ]);

        $managedClientFile = FileUpload::factory()->create(['client_user_id' => $this->client1->id]);
        $unmanagedClientFile = FileUpload::factory()->create(['client_user_id' => $this->client2->id]);

        $this->assertTrue($managedClientFile->canBeAccessedBy($this->employee));
        $this->assertFalse($unmanagedClientFile->canBeAccessedBy($this->employee));
    }

    /** @test */
    public function employee_can_access_files_they_uploaded()
    {
        $uploadedFile = FileUpload::factory()->create(['uploaded_by_user_id' => $this->employee->id]);
        $otherFile = FileUpload::factory()->create(['uploaded_by_user_id' => $this->admin->id]);

        $this->assertTrue($uploadedFile->canBeAccessedBy($this->employee));
        $this->assertFalse($otherFile->canBeAccessedBy($this->employee));
    }

    /** @test */
    public function employee_can_access_both_managed_and_uploaded_files()
    {
        // Create relationship
        ClientUserRelationship::create([
            'company_user_id' => $this->employee->id,
            'client_user_id' => $this->client1->id,
            'is_primary' => true
        ]);

        $managedFile = FileUpload::factory()->create(['client_user_id' => $this->client1->id]);
        $uploadedFile = FileUpload::factory()->create(['uploaded_by_user_id' => $this->employee->id]);

        $this->assertTrue($managedFile->canBeAccessedBy($this->employee));
        $this->assertTrue($uploadedFile->canBeAccessedBy($this->employee));
    }

    /** @test */
    public function accessible_by_scope_filters_correctly_for_admin()
    {
        $files = FileUpload::factory()->count(5)->create();
        
        $accessibleFiles = FileUpload::accessibleBy($this->admin)->get();

        $this->assertCount(5, $accessibleFiles);
    }

    /** @test */
    public function accessible_by_scope_filters_correctly_for_client()
    {
        FileUpload::factory()->create(['client_user_id' => $this->client1->id]);
        FileUpload::factory()->create(['client_user_id' => $this->client1->id]);
        FileUpload::factory()->create(['client_user_id' => $this->client2->id]);
        FileUpload::factory()->create(['client_user_id' => null]);

        $accessibleFiles = FileUpload::accessibleBy($this->client1)->get();

        $this->assertCount(2, $accessibleFiles);
        $this->assertTrue($accessibleFiles->every(fn($file) => $file->client_user_id === $this->client1->id));
    }

    /** @test */
    public function accessible_by_scope_filters_correctly_for_employee()
    {
        // Create relationship
        ClientUserRelationship::create([
            'company_user_id' => $this->employee->id,
            'client_user_id' => $this->client1->id,
            'is_primary' => true
        ]);

        $managedFile = FileUpload::factory()->create(['client_user_id' => $this->client1->id]);
        $unmanagedFile = FileUpload::factory()->create(['client_user_id' => $this->client2->id]);
        $uploadedFile = FileUpload::factory()->create(['uploaded_by_user_id' => $this->employee->id]);
        $otherFile = FileUpload::factory()->create(['uploaded_by_user_id' => $this->admin->id]);

        $accessibleFiles = FileUpload::accessibleBy($this->employee)->get();

        $this->assertCount(2, $accessibleFiles);
        $this->assertTrue($accessibleFiles->contains($managedFile));
        $this->assertTrue($accessibleFiles->contains($uploadedFile));
        $this->assertFalse($accessibleFiles->contains($unmanagedFile));
        $this->assertFalse($accessibleFiles->contains($otherFile));
    }

    /** @test */
    public function accessible_by_scope_returns_empty_for_unknown_role()
    {
        // Create a user and then manually change their role to test the fallback
        $unknownUser = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Manually update the role in the database to an invalid value
        // This simulates a corrupted or unknown role scenario
        \DB::table('users')->where('id', $unknownUser->id)->update(['role' => 'unknown']);
        
        FileUpload::factory()->count(3)->create();

        // This should trigger the default case in the scope which returns no results
        try {
            $accessibleFiles = FileUpload::accessibleBy($unknownUser->fresh())->get();
            $this->assertCount(0, $accessibleFiles);
        } catch (\ValueError $e) {
            // If enum validation fails, that's also acceptable behavior
            $this->assertStringContainsString('not a valid backing value', $e->getMessage());
        }
    }

    /** @test */
    public function employee_with_multiple_client_relationships()
    {
        // Create relationships with multiple clients
        ClientUserRelationship::create([
            'company_user_id' => $this->employee->id,
            'client_user_id' => $this->client1->id,
            'is_primary' => true
        ]);
        
        ClientUserRelationship::create([
            'company_user_id' => $this->employee->id,
            'client_user_id' => $this->client2->id,
            'is_primary' => false
        ]);

        $client1File = FileUpload::factory()->create(['client_user_id' => $this->client1->id]);
        $client2File = FileUpload::factory()->create(['client_user_id' => $this->client2->id]);

        $this->assertTrue($client1File->canBeAccessedBy($this->employee));
        $this->assertTrue($client2File->canBeAccessedBy($this->employee));

        $accessibleFiles = FileUpload::accessibleBy($this->employee)->get();
        $this->assertCount(2, $accessibleFiles);
    }

    /** @test */
    public function permission_check_handles_null_values()
    {
        $fileWithNullClient = FileUpload::factory()->create(['client_user_id' => null]);
        $fileWithNullUploader = FileUpload::factory()->create(['uploaded_by_user_id' => null]);

        // Admin should access all files regardless of null values
        $this->assertTrue($fileWithNullClient->canBeAccessedBy($this->admin));
        $this->assertTrue($fileWithNullUploader->canBeAccessedBy($this->admin));

        // Client should not access files with null client_user_id
        $this->assertFalse($fileWithNullClient->canBeAccessedBy($this->client1));
        $this->assertFalse($fileWithNullUploader->canBeAccessedBy($this->client1));

        // Employee should not access files with null values unless they have other permissions
        $this->assertFalse($fileWithNullClient->canBeAccessedBy($this->employee));
        $this->assertFalse($fileWithNullUploader->canBeAccessedBy($this->employee));
    }

    /** @test */
    public function permission_check_with_complex_scenarios()
    {
        // Create a file uploaded by employee for a client they don't manage
        $file = FileUpload::factory()->create([
            'client_user_id' => $this->client2->id,
            'uploaded_by_user_id' => $this->employee->id
        ]);

        // Employee should be able to access it because they uploaded it
        $this->assertTrue($file->canBeAccessedBy($this->employee));

        // Client2 should be able to access it because it's their file
        $this->assertTrue($file->canBeAccessedBy($this->client2));

        // Client1 should not be able to access it
        $this->assertFalse($file->canBeAccessedBy($this->client1));

        // Admin should be able to access it
        $this->assertTrue($file->canBeAccessedBy($this->admin));
    }

    /** @test */
    public function accessible_by_scope_with_complex_employee_permissions()
    {
        // Create relationship
        ClientUserRelationship::create([
            'company_user_id' => $this->employee->id,
            'client_user_id' => $this->client1->id,
            'is_primary' => true
        ]);

        // Create various files
        $managedClientFile = FileUpload::factory()->create(['client_user_id' => $this->client1->id]);
        $unmanagedClientFile = FileUpload::factory()->create(['client_user_id' => $this->client2->id]);
        $employeeUploadedForManagedClient = FileUpload::factory()->create([
            'client_user_id' => $this->client1->id,
            'uploaded_by_user_id' => $this->employee->id
        ]);
        $employeeUploadedForUnmanagedClient = FileUpload::factory()->create([
            'client_user_id' => $this->client2->id,
            'uploaded_by_user_id' => $this->employee->id
        ]);
        $employeeUploadedSystemFile = FileUpload::factory()->create([
            'client_user_id' => null,
            'uploaded_by_user_id' => $this->employee->id
        ]);

        $accessibleFiles = FileUpload::accessibleBy($this->employee)->get();

        // Should include: managed client file, both employee uploaded files, and system file uploaded by employee
        $this->assertCount(4, $accessibleFiles);
        $this->assertTrue($accessibleFiles->contains($managedClientFile));
        $this->assertTrue($accessibleFiles->contains($employeeUploadedForManagedClient));
        $this->assertTrue($accessibleFiles->contains($employeeUploadedForUnmanagedClient));
        $this->assertTrue($accessibleFiles->contains($employeeUploadedSystemFile));
        $this->assertFalse($accessibleFiles->contains($unmanagedClientFile));
    }

    /** @test */
    public function permission_methods_handle_user_role_changes()
    {
        $file = FileUpload::factory()->create(['client_user_id' => $this->client1->id]);

        // Initially client can access their file
        $this->assertTrue($file->canBeAccessedBy($this->client1));

        // Change client to employee role
        $this->client1->update(['role' => UserRole::EMPLOYEE]);
        $this->client1->refresh();

        // Now as employee, they shouldn't be able to access the file (no relationship)
        $this->assertFalse($file->canBeAccessedBy($this->client1));

        // Change to admin role
        $this->client1->update(['role' => UserRole::ADMIN]);
        $this->client1->refresh();

        // Now as admin, they should be able to access all files
        $this->assertTrue($file->canBeAccessedBy($this->client1));
    }
}