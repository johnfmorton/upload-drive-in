<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\FileUpload;
use App\Enums\UserRole;
use App\Enums\CloudStorageErrorType;
use App\Jobs\UploadToGoogleDrive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminFileManagerErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private FileUpload $fileWithError;
    private FileUpload $fileWithRecoverableError;
    private FileUpload $fileWithoutError;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@test.com'
        ]);

        $this->client = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client@test.com'
        ]);

        // Create test files with different error states
        $this->fileWithError = FileUpload::factory()->create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->admin->id, // Admin should be the company user
            'original_filename' => 'test-error.pdf',
            'cloud_storage_error_type' => CloudStorageErrorType::TOKEN_EXPIRED->value,
            'cloud_storage_error_context' => ['provider' => 'google-drive'],
        ]);

        $this->fileWithRecoverableError = FileUpload::factory()->create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->admin->id, // Admin should be the company user
            'original_filename' => 'test-recoverable.pdf',
            'cloud_storage_error_type' => CloudStorageErrorType::NETWORK_ERROR->value,
            'cloud_storage_error_context' => ['provider' => 'google-drive'],
        ]);

        $this->fileWithoutError = FileUpload::factory()->create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->admin->id, // Admin should be the company user
            'original_filename' => 'test-success.pdf',
            'google_drive_file_id' => 'drive-file-123',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_view_files_with_error_information()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/admin/file-manager');

        $response->assertOk();
        
        $files = $response->json('files.data');
        $errorFile = collect($files)->firstWhere('id', $this->fileWithError->id);
        
        $this->assertNotNull($errorFile);
        $this->assertEquals(CloudStorageErrorType::TOKEN_EXPIRED->value, $errorFile['cloud_storage_error_type']);
        $this->assertNotNull($errorFile['cloud_storage_error_message']);
        $this->assertNotNull($errorFile['cloud_storage_error_description']);
        $this->assertEquals('high', $errorFile['cloud_storage_error_severity']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_retry_file_with_recoverable_error()
    {
        Queue::fake();

        $response = $this->actingAs($this->admin)
            ->postJson("/admin/file-manager/{$this->fileWithRecoverableError->id}/retry");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'File upload retry initiated successfully'
            ]);

        // Verify error information was cleared
        $this->fileWithRecoverableError->refresh();
        $this->assertNull($this->fileWithRecoverableError->cloud_storage_error_type);

        // Verify job was dispatched
        Queue::assertPushed(UploadToGoogleDrive::class, function ($job) {
            // Use reflection to access the protected property
            $reflection = new \ReflectionClass($job);
            $property = $reflection->getProperty('fileUploadId');
            $property->setAccessible(true);
            return $property->getValue($job) === $this->fileWithRecoverableError->id;
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_cannot_retry_file_with_non_recoverable_error()
    {
        Queue::fake();

        $response = $this->actingAs($this->admin)
            ->postJson("/admin/file-manager/{$this->fileWithError->id}/retry");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'This error type cannot be automatically retried. Please check your cloud storage connection.'
            ]);

        // Verify error information was not cleared
        $this->fileWithError->refresh();
        $this->assertEquals(CloudStorageErrorType::TOKEN_EXPIRED->value, $this->fileWithError->cloud_storage_error_type);

        // Verify no job was dispatched
        Queue::assertNotPushed(UploadToGoogleDrive::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_cannot_retry_file_without_error()
    {
        Queue::fake();

        $response = $this->actingAs($this->admin)
            ->postJson("/admin/file-manager/{$this->fileWithoutError->id}/retry");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'File does not have a cloud storage error to retry'
            ]);

        // Verify no job was dispatched
        Queue::assertNotPushed(UploadToGoogleDrive::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_bulk_retry_recoverable_files()
    {
        Queue::fake();

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/file-manager/bulk-retry', [
                'file_ids' => [
                    $this->fileWithRecoverableError->id,
                    $this->fileWithError->id, // Non-recoverable, should be skipped
                    $this->fileWithoutError->id // No error, should be skipped
                ]
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'retried_count' => 1,
                'failed_count' => 0
            ]);

        // Verify only recoverable error was cleared
        $this->fileWithRecoverableError->refresh();
        $this->assertNull($this->fileWithRecoverableError->cloud_storage_error_type);

        $this->fileWithError->refresh();
        $this->assertEquals(CloudStorageErrorType::TOKEN_EXPIRED->value, $this->fileWithError->cloud_storage_error_type);

        // Verify only one job was dispatched
        Queue::assertPushed(UploadToGoogleDrive::class, 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_filter_files_by_error_status()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/admin/file-manager?error_status=has_error');

        $response->assertOk();
        
        $files = $response->json('files.data');
        $this->assertCount(2, $files); // Both error files should be returned
        
        $fileIds = collect($files)->pluck('id')->toArray();
        $this->assertContains($this->fileWithError->id, $fileIds);
        $this->assertContains($this->fileWithRecoverableError->id, $fileIds);
        $this->assertNotContains($this->fileWithoutError->id, $fileIds);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_filter_files_by_recoverable_errors()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/admin/file-manager?error_status=recoverable');

        $response->assertOk();
        
        $files = $response->json('files.data');
        $this->assertCount(1, $files); // Only recoverable error file should be returned
        
        $this->assertEquals($this->fileWithRecoverableError->id, $files[0]['id']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_filter_files_by_error_severity()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/admin/file-manager?error_severity=high');

        $response->assertOk();
        
        $files = $response->json('files.data');
        $this->assertCount(1, $files); // Only high severity error file should be returned
        
        $this->assertEquals($this->fileWithError->id, $files[0]['id']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function non_admin_cannot_access_retry_endpoints()
    {
        $response = $this->actingAs($this->client)
            ->postJson("/admin/file-manager/{$this->fileWithRecoverableError->id}/retry");

        $response->assertStatus(403); // Non-admin users get 403 when trying to access admin files

        $response = $this->actingAs($this->client)
            ->postJson('/admin/file-manager/bulk-retry', [
                'file_ids' => [$this->fileWithRecoverableError->id]
            ]);

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function guest_cannot_access_retry_endpoints()
    {
        $response = $this->postJson("/admin/file-manager/{$this->fileWithRecoverableError->id}/retry");
        $response->assertStatus(401); // Unauthenticated users get 401

        $response = $this->postJson('/admin/file-manager/bulk-retry', [
            'file_ids' => [$this->fileWithRecoverableError->id]
        ]);
        $response->assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function bulk_retry_validates_request_data()
    {
        // Test missing file_ids
        $response = $this->actingAs($this->admin)
            ->postJson('/admin/file-manager/bulk-retry', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file_ids']);

        // Test empty file_ids array
        $response = $this->actingAs($this->admin)
            ->postJson('/admin/file-manager/bulk-retry', [
                'file_ids' => []
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file_ids']);

        // Test invalid file_ids
        $response = $this->actingAs($this->admin)
            ->postJson('/admin/file-manager/bulk-retry', [
                'file_ids' => [99999] // Non-existent file ID
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file_ids.0']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function retry_endpoints_handle_file_access_permissions()
    {
        // Create another admin who shouldn't have access to the file
        $otherAdmin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'other-admin@test.com'
        ]);

        // Create a file that belongs to a specific employee's client
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $employeeClient = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create relationship between employee and client
        $employee->clientUsers()->attach($employeeClient->id);
        
        $restrictedFile = FileUpload::factory()->create([
            'client_user_id' => $employeeClient->id,
            'cloud_storage_error_type' => CloudStorageErrorType::NETWORK_ERROR->value,
        ]);

        // Admin should still have access (admins can access all files)
        $response = $this->actingAs($this->admin)
            ->postJson("/admin/file-manager/{$restrictedFile->id}/retry");

        $response->assertOk();
    }
}