<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\FileUpload;
use App\Enums\UserRole;
use App\Jobs\UploadToGoogleDrive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminFileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Queue::fake();
    }

    public function test_admin_can_receive_file_uploads()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN,
        ]);

        $file = UploadedFile::fake()->create('test-document.pdf', 100);

        $response = $this->post('/upload/admin', [
            'files' => [$file],
            'email' => 'client@example.com',
            'message' => 'Test upload for admin',
        ]);

        $response->assertRedirect('/upload/admin');
        $response->assertSessionHas('success');

        // Verify file upload record was created
        $this->assertDatabaseHas('file_uploads', [
            'email' => 'client@example.com',
            'original_filename' => 'test-document.pdf',
            'message' => 'Test upload for admin',
            'uploaded_by_user_id' => $admin->id,
            'validation_method' => 'employee_public',
        ]);

        // Verify file was stored
        $upload = FileUpload::where('email', 'client@example.com')->first();
        Storage::disk('public')->assertExists('uploads/' . $upload->filename);

        // Verify Google Drive upload job was dispatched
        Queue::assertPushed(UploadToGoogleDrive::class);
    }

    public function test_employee_can_still_receive_file_uploads()
    {
        $employee = User::factory()->create([
            'email' => 'employee@company.com',
            'role' => UserRole::EMPLOYEE,
        ]);

        $file = UploadedFile::fake()->create('test-document.pdf', 100);

        $response = $this->post('/upload/employee', [
            'files' => [$file],
            'email' => 'client@example.com',
            'message' => 'Test upload for employee',
        ]);

        $response->assertRedirect('/upload/employee');
        $response->assertSessionHas('success');

        // Verify file upload record was created
        $this->assertDatabaseHas('file_uploads', [
            'email' => 'client@example.com',
            'original_filename' => 'test-document.pdf',
            'message' => 'Test upload for employee',
            'uploaded_by_user_id' => $employee->id,
            'validation_method' => 'employee_public',
        ]);

        // Verify Google Drive upload job was dispatched
        Queue::assertPushed(UploadToGoogleDrive::class);
    }

    public function test_client_cannot_receive_file_uploads()
    {
        User::factory()->create([
            'email' => 'client@example.com',
            'role' => UserRole::CLIENT,
        ]);

        $file = UploadedFile::fake()->create('test-document.pdf', 100);

        $response = $this->post('/upload/client', [
            'files' => [$file],
            'email' => 'sender@example.com',
            'message' => 'Test upload for client',
        ]);

        $response->assertStatus(404);

        // Verify no file upload record was created
        $this->assertDatabaseMissing('file_uploads', [
            'email' => 'sender@example.com',
        ]);
    }
}