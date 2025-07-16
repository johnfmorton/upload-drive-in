<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\FileUpload;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class FilePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_admin_can_preview_image_file()
    {
        // Create admin user
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create a simple test image
        $image = imagecreate(10, 10);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        
        ob_start();
        imagepng($image);
        $imageContent = ob_get_clean();
        imagedestroy($image);
        
        // Store test file
        Storage::disk('public')->put('uploads/test-image.png', $imageContent);
        
        // Create file upload record
        $file = FileUpload::factory()->create([
            'filename' => 'test-image.png',
            'original_filename' => 'test-image.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($imageContent),
            'client_user_id' => null,
        ]);

        // Test preview endpoint
        $response = $this->actingAs($admin)
            ->get(route('files.preview', $file));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_admin_can_preview_text_file()
    {
        // Create admin user
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $textContent = "Hello, World!\nThis is a test file.";
        
        // Store test file
        Storage::disk('public')->put('uploads/test.txt', $textContent);
        
        // Create file upload record
        $file = FileUpload::factory()->create([
            'filename' => 'test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen($textContent),
            'client_user_id' => null,
        ]);

        // Test preview endpoint
        $response = $this->actingAs($admin)
            ->get(route('files.preview', $file));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->assertEquals($textContent, $response->getContent());
    }

    public function test_admin_can_generate_thumbnail_for_image()
    {
        // Create admin user
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create a simple test image
        $image = imagecreate(50, 50);
        $white = imagecolorallocate($image, 255, 255, 255);
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $white);
        imagesetpixel($image, 25, 25, $red);
        
        ob_start();
        imagepng($image);
        $imageContent = ob_get_clean();
        imagedestroy($image);
        
        // Store test file
        Storage::disk('public')->put('uploads/test-image.png', $imageContent);
        
        // Create file upload record
        $file = FileUpload::factory()->create([
            'filename' => 'test-image.png',
            'original_filename' => 'test-image.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($imageContent),
            'client_user_id' => null,
        ]);

        // Test thumbnail endpoint
        $response = $this->actingAs($admin)
            ->get(route('files.thumbnail', $file));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');
        $this->assertStringContainsString('thumb_test-image.png', $response->headers->get('Content-Disposition'));
    }

    public function test_thumbnail_returns_404_for_non_image_files()
    {
        // Create admin user
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create file upload record for non-image type
        $file = FileUpload::factory()->create([
            'filename' => 'test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
            'client_user_id' => null,
        ]);

        // Test thumbnail endpoint
        $response = $this->actingAs($admin)
            ->get(route('files.thumbnail', $file));

        $response->assertStatus(404);
        $this->assertStringContainsString('Thumbnail not available', $response->getContent());
    }

    public function test_preview_returns_error_for_unsupported_file_types()
    {
        // Create admin user
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create file upload record for unsupported type
        $file = FileUpload::factory()->create([
            'filename' => 'test.zip',
            'original_filename' => 'test.zip',
            'mime_type' => 'application/zip',
            'file_size' => 1024,
            'client_user_id' => null,
        ]);

        // Test preview endpoint
        $response = $this->actingAs($admin)
            ->get(route('files.preview', $file));

        $response->assertStatus(404);
        $this->assertStringContainsString('Preview not available', $response->getContent());
    }

    public function test_preview_returns_error_for_missing_files()
    {
        // Create admin user
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create file upload record but don't store the actual file
        $file = FileUpload::factory()->create([
            'filename' => 'missing-file.txt',
            'original_filename' => 'missing-file.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
            'client_user_id' => null,
            'google_drive_file_id' => null,
        ]);

        // Test preview endpoint
        $response = $this->actingAs($admin)
            ->get(route('files.preview', $file));

        $response->assertStatus(404);
        $this->assertStringContainsString('Preview not available', $response->getContent());
    }

    public function test_client_can_only_preview_their_own_files()
    {
        // Create client users
        $client1 = User::factory()->create(['role' => UserRole::CLIENT]);
        $client2 = User::factory()->create(['role' => UserRole::CLIENT]);
        
        $textContent = "Private file content";
        
        // Store test file
        Storage::disk('public')->put('uploads/private.txt', $textContent);
        
        // Create file upload record owned by client1
        $file = FileUpload::factory()->create([
            'filename' => 'private.txt',
            'original_filename' => 'private.txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen($textContent),
            'client_user_id' => $client1->id,
        ]);

        // Client1 should be able to preview their own file
        $response = $this->actingAs($client1)
            ->get(route('files.preview', $file));

        $response->assertStatus(200);
        $this->assertEquals($textContent, $response->getContent());

        // Client2 should not be able to preview client1's file
        $response = $this->actingAs($client2)
            ->get(route('files.preview', $file));

        $response->assertStatus(404);
        $this->assertStringContainsString('Access denied', $response->getContent());
    }

    public function test_employee_can_preview_files_from_managed_clients()
    {
        // Create users
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create relationship between employee and client
        $employee->clientUsers()->attach($client->id);
        
        $textContent = "Client file content";
        
        // Store test file
        Storage::disk('public')->put('uploads/client-file.txt', $textContent);
        
        // Create file upload record owned by client
        $file = FileUpload::factory()->create([
            'filename' => 'client-file.txt',
            'original_filename' => 'client-file.txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen($textContent),
            'client_user_id' => $client->id,
        ]);

        // Employee should be able to preview client's file
        $response = $this->actingAs($employee)
            ->get(route('files.preview', $file));

        $response->assertStatus(200);
        $this->assertEquals($textContent, $response->getContent());
    }

    public function test_employee_cannot_preview_files_from_unmanaged_clients()
    {
        // Create users
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // No relationship between employee and client
        
        $textContent = "Client file content";
        
        // Store test file
        Storage::disk('public')->put('uploads/client-file.txt', $textContent);
        
        // Create file upload record owned by client
        $file = FileUpload::factory()->create([
            'filename' => 'client-file.txt',
            'original_filename' => 'client-file.txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen($textContent),
            'client_user_id' => $client->id,
        ]);

        // Employee should not be able to preview unmanaged client's file
        $response = $this->actingAs($employee)
            ->get(route('files.preview', $file));

        $response->assertStatus(404);
        $this->assertStringContainsString('Access denied', $response->getContent());
    }

    public function test_unauthenticated_users_cannot_access_preview_endpoints()
    {
        // Create file upload record
        $file = FileUpload::factory()->create([
            'filename' => 'test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
        ]);

        // Test preview endpoint without authentication
        $response = $this->get(route('files.preview', $file));
        $response->assertRedirect(route('login'));

        // Test thumbnail endpoint without authentication
        $response = $this->get(route('files.thumbnail', $file));
        $response->assertRedirect(route('login'));
    }
}