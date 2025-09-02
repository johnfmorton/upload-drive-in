<?php

namespace Tests\Feature;

use App\Jobs\UploadToGoogleDrive;
use App\Models\FileUpload;
use App\Models\User;
use App\Services\CloudStorageManager;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UploadToCloudStorageJobIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_job_uses_cloud_storage_manager_for_provider_resolution()
    {
        // Create test user and file upload
        $user = User::factory()->create(['role' => 'admin']);
        $fileUpload = FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'filename' => 'test-file.txt',
            'original_filename' => 'test-file.txt',
        ]);

        // Create the test file
        Storage::disk('public')->put('uploads/test-file.txt', 'test content');

        // Mock the CloudStorageManager
        $mockManager = $this->createMock(CloudStorageManager::class);
        $mockProvider = $this->createMock(\App\Contracts\CloudStorageProviderInterface::class);

        $mockProvider->method('getProviderName')->willReturn('google-drive');
        $mockProvider->method('hasValidConnection')->willReturn(true);
        $mockProvider->method('uploadFile')->willReturn('mock-file-id');

        $mockManager->method('getUserProvider')->willReturn($mockProvider);

        $this->app->instance(CloudStorageManager::class, $mockManager);

        // Create and handle the job
        $job = new UploadToGoogleDrive($fileUpload);
        $job->handle(
            $mockManager,
            app(CloudStorageHealthService::class),
            app(CloudStorageLogService::class)
        );

        // Assert the file upload was updated with the cloud file ID
        $fileUpload->refresh();
        $this->assertEquals('mock-file-id', $fileUpload->google_drive_file_id);
        $this->assertEquals('google-drive', $fileUpload->cloud_storage_provider);
        $this->assertNull($fileUpload->cloud_storage_error_type);
    }

    public function test_job_handles_provider_resolution_failure()
    {
        // Create test user and file upload
        $user = User::factory()->create(['role' => 'admin']);
        $fileUpload = FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'filename' => 'test-file.txt',
            'original_filename' => 'test-file.txt',
        ]);

        // Create the test file
        Storage::disk('public')->put('uploads/test-file.txt', 'test content');

        // Mock the CloudStorageManager to throw exception
        $mockManager = $this->createMock(CloudStorageManager::class);
        $mockManager->method('getUserProvider')
            ->willThrowException(new \Exception('No provider available'));

        $this->app->instance(CloudStorageManager::class, $mockManager);

        // Expect the job to fail
        $this->expectException(\Exception::class);

        // Create and handle the job
        $job = new UploadToGoogleDrive($fileUpload);
        $job->handle(
            $mockManager,
            app(CloudStorageHealthService::class),
            app(CloudStorageLogService::class)
        );
    }

    public function test_job_falls_back_to_admin_user_when_target_user_has_no_connection()
    {
        // Create admin and employee users
        $adminUser = User::factory()->create(['role' => 'admin']);
        $employeeUser = User::factory()->create(['role' => 'employee']);
        
        $fileUpload = FileUpload::factory()->create([
            'company_user_id' => $employeeUser->id,
            'filename' => 'test-file.txt',
            'original_filename' => 'test-file.txt',
        ]);

        // Create the test file
        Storage::disk('public')->put('uploads/test-file.txt', 'test content');

        // Mock the CloudStorageManager
        $mockManager = $this->createMock(CloudStorageManager::class);
        $mockProvider = $this->createMock(\App\Contracts\CloudStorageProviderInterface::class);

        $mockProvider->method('getProviderName')->willReturn('google-drive');
        $mockProvider->method('uploadFile')->willReturn('mock-file-id');
        
        // Employee has no connection, admin has connection
        $mockProvider->method('hasValidConnection')
            ->willReturnCallback(function($user) use ($adminUser, $employeeUser) {
                return $user->id === $adminUser->id;
            });

        $mockManager->method('getUserProvider')->willReturn($mockProvider);

        $this->app->instance(CloudStorageManager::class, $mockManager);

        // Create and handle the job
        $job = new UploadToGoogleDrive($fileUpload);
        $job->handle(
            $mockManager,
            app(CloudStorageHealthService::class),
            app(CloudStorageLogService::class)
        );

        // Assert the file upload was successful
        $fileUpload->refresh();
        $this->assertEquals('mock-file-id', $fileUpload->google_drive_file_id);
        $this->assertEquals('google-drive', $fileUpload->cloud_storage_provider);
    }
}