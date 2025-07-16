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

    public function test_admin_can_access_all_files()
    {
        // Create an admin user
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create a client user and file
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $file = FileUpload::factory()->create(['client_user_id' => $client->id]);

        $this->assertTrue($file->canBeAccessedBy($admin));
    }

    public function test_client_can_only_access_their_own_files()
    {
        // Create two client users
        $client1 = User::factory()->create(['role' => UserRole::CLIENT]);
        $client2 = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create files for each client
        $file1 = FileUpload::factory()->create(['client_user_id' => $client1->id]);
        $file2 = FileUpload::factory()->create(['client_user_id' => $client2->id]);

        // Client 1 can access their own file but not client 2's file
        $this->assertTrue($file1->canBeAccessedBy($client1));
        $this->assertFalse($file2->canBeAccessedBy($client1));
        
        // Client 2 can access their own file but not client 1's file
        $this->assertTrue($file2->canBeAccessedBy($client2));
        $this->assertFalse($file1->canBeAccessedBy($client2));
    }

    public function test_employee_can_access_files_from_managed_clients()
    {
        // Create an employee and a client
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create a relationship between employee and client
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client->id,
            'is_primary' => true,
        ]);
        
        // Create a file uploaded by the client
        $file = FileUpload::factory()->create(['client_user_id' => $client->id]);

        $this->assertTrue($file->canBeAccessedBy($employee));
    }

    public function test_employee_cannot_access_files_from_unmanaged_clients()
    {
        // Create an employee and two clients
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $managedClient = User::factory()->create(['role' => UserRole::CLIENT]);
        $unmanagedClient = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create a relationship only with the managed client
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $managedClient->id,
            'is_primary' => true,
        ]);
        
        // Create files for both clients
        $managedFile = FileUpload::factory()->create(['client_user_id' => $managedClient->id]);
        $unmanagedFile = FileUpload::factory()->create(['client_user_id' => $unmanagedClient->id]);

        // Employee can access managed client's file but not unmanaged client's file
        $this->assertTrue($managedFile->canBeAccessedBy($employee));
        $this->assertFalse($unmanagedFile->canBeAccessedBy($employee));
    }

    public function test_employee_can_access_files_they_uploaded()
    {
        // Create an employee
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        
        // Create a file uploaded by the employee
        $file = FileUpload::factory()->create(['uploaded_by_user_id' => $employee->id]);

        $this->assertTrue($file->canBeAccessedBy($employee));
    }

    public function test_accessible_by_scope_returns_correct_files_for_admin()
    {
        // Create an admin user
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create multiple files
        FileUpload::factory()->count(3)->create();

        $accessibleFiles = FileUpload::accessibleBy($admin)->get();

        // Admin should see all files
        $this->assertCount(3, $accessibleFiles);
    }

    public function test_accessible_by_scope_returns_correct_files_for_client()
    {
        // Create two client users
        $client1 = User::factory()->create(['role' => UserRole::CLIENT]);
        $client2 = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create files for each client
        FileUpload::factory()->create(['client_user_id' => $client1->id]);
        FileUpload::factory()->create(['client_user_id' => $client1->id]);
        FileUpload::factory()->create(['client_user_id' => $client2->id]);

        $accessibleFiles = FileUpload::accessibleBy($client1)->get();

        // Client 1 should only see their own files
        $this->assertCount(2, $accessibleFiles);
        $this->assertTrue($accessibleFiles->every(fn($file) => $file->client_user_id === $client1->id));
    }

    public function test_accessible_by_scope_returns_correct_files_for_employee()
    {
        // Create an employee and clients
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $managedClient = User::factory()->create(['role' => UserRole::CLIENT]);
        $unmanagedClient = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create relationship with managed client
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $managedClient->id,
            'is_primary' => true,
        ]);
        
        // Create files
        $managedFile = FileUpload::factory()->create(['client_user_id' => $managedClient->id]);
        $unmanagedFile = FileUpload::factory()->create(['client_user_id' => $unmanagedClient->id]);
        $employeeFile = FileUpload::factory()->create(['uploaded_by_user_id' => $employee->id]);

        $accessibleFiles = FileUpload::accessibleBy($employee)->get();

        // Employee should see managed client's file and their own uploaded file
        $this->assertCount(2, $accessibleFiles);
        $this->assertTrue($accessibleFiles->contains($managedFile));
        $this->assertTrue($accessibleFiles->contains($employeeFile));
        $this->assertFalse($accessibleFiles->contains($unmanagedFile));
    }

    public function test_is_previewable_returns_true_for_supported_mime_types()
    {
        $supportedMimeTypes = [
            'image/jpeg',
            'image/png',
            'application/pdf',
            'text/plain',
            'application/json',
        ];

        foreach ($supportedMimeTypes as $mimeType) {
            $file = FileUpload::factory()->create(['mime_type' => $mimeType]);
            $this->assertTrue($file->isPreviewable(), "Failed for MIME type: {$mimeType}");
        }
    }

    public function test_is_previewable_returns_false_for_unsupported_mime_types()
    {
        $unsupportedMimeTypes = [
            'application/zip',
            'video/mp4',
            'audio/mpeg',
            'application/vnd.ms-excel',
        ];

        foreach ($unsupportedMimeTypes as $mimeType) {
            $file = FileUpload::factory()->create(['mime_type' => $mimeType]);
            $this->assertFalse($file->isPreviewable(), "Failed for MIME type: {$mimeType}");
        }
    }

    public function test_get_preview_url_returns_null_for_non_previewable_files()
    {
        $file = FileUpload::factory()->create(['mime_type' => 'application/zip']);
        $this->assertNull($file->getPreviewUrl());
    }
}