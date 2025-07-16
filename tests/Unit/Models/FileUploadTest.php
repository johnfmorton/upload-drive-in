<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\FileUpload;
use App\Models\User;
use App\Models\ClientUserRelationship;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the routes that don't exist yet
        Route::get('/admin/files/{file}/preview', function ($file) {
            return response('preview');
        })->name('admin.files.preview');
        
        Route::get('/admin/files/{file}/download', function ($file) {
            return response('download');
        })->name('admin.files.download');
        
        Route::get('/admin/files/{file}/thumbnail', function ($file) {
            return response('thumbnail');
        })->name('admin.files.thumbnail');
    }

    /** @test */
    public function admin_can_access_all_files()
    {
        // Create an admin user
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create a client user and file
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $file = FileUpload::factory()->create(['client_user_id' => $client->id]);

        $this->assertTrue($file->canBeAccessedBy($admin));
    }

    /** @test */
    public function client_can_only_access_their_own_files()
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

    /** @test */
    public function employee_can_access_files_from_managed_clients()
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

    /** @test */
    public function employee_cannot_access_files_from_unmanaged_clients()
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

    /** @test */
    public function employee_can_access_files_they_uploaded()
    {
        // Create an employee
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        
        // Create a file uploaded by the employee
        $file = FileUpload::factory()->create(['uploaded_by_user_id' => $employee->id]);

        $this->assertTrue($file->canBeAccessedBy($employee));
    }

    /** @test */
    public function accessible_by_scope_returns_correct_files_for_admin()
    {
        // Create an admin user
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create multiple files
        $files = FileUpload::factory()->count(3)->create();

        $accessibleFiles = FileUpload::accessibleBy($admin)->get();

        // Admin should see all files
        $this->assertCount(3, $accessibleFiles);
    }

    /** @test */
    public function accessible_by_scope_returns_correct_files_for_client()
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

    /** @test */
    public function accessible_by_scope_returns_correct_files_for_employee()
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

    /** @test */
    public function is_previewable_returns_true_for_supported_mime_types()
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

    /** @test */
    public function is_previewable_returns_false_for_unsupported_mime_types()
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

    /** @test */
    public function get_preview_url_returns_null_for_non_previewable_files()
    {
        $file = FileUpload::factory()->create(['mime_type' => 'application/zip']);
        $this->assertNull($file->getPreviewUrl());
    }

    /** @test */
    public function get_preview_url_returns_route_for_previewable_files()
    {
        $file = FileUpload::factory()->create(['mime_type' => 'image/jpeg']);
        $expectedUrl = route('admin.files.preview', $file);
        $this->assertEquals($expectedUrl, $file->getPreviewUrl());
    }

    /** @test */
    public function get_download_url_returns_correct_route()
    {
        $file = FileUpload::factory()->create();
        $expectedUrl = route('admin.files.download', $file);
        $this->assertEquals($expectedUrl, $file->getDownloadUrl());
    }

    /** @test */
    public function get_thumbnail_url_returns_null_for_non_image_files()
    {
        $file = FileUpload::factory()->create(['mime_type' => 'application/pdf']);
        $this->assertNull($file->getThumbnailUrl());
    }

    /** @test */
    public function get_thumbnail_url_returns_route_for_image_files()
    {
        $file = FileUpload::factory()->create(['mime_type' => 'image/jpeg']);
        $expectedUrl = route('admin.files.thumbnail', $file);
        $this->assertEquals($expectedUrl, $file->getThumbnailUrl());
    }

    /** @test */
    public function appended_attributes_are_correctly_calculated()
    {
        $file = FileUpload::factory()->create([
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
        ]);

        $array = $file->toArray();

        $this->assertArrayHasKey('can_preview', $array);
        $this->assertArrayHasKey('preview_url', $array);
        $this->assertArrayHasKey('download_url', $array);
        $this->assertArrayHasKey('thumbnail_url', $array);
        $this->assertArrayHasKey('file_size_human', $array);

        $this->assertTrue($array['can_preview']);
        $this->assertNotNull($array['preview_url']);
        $this->assertNotNull($array['download_url']);
        $this->assertNotNull($array['thumbnail_url']);
        $this->assertEquals('1.00 KB', $array['file_size_human']);
    }
}