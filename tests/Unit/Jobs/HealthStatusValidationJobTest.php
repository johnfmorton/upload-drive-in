<?php

namespace Tests\Unit\Jobs;

use App\Jobs\HealthStatusValidationJob;
use App\Models\User;
use App\Models\FileUpload;
use App\Models\GoogleDriveToken;
use App\Models\CloudStorageHealthStatus;
use App\Services\RealTimeHealthValidator;
use App\Services\CloudStorageHealthService;
use App\Services\HealthStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class HealthStatusValidationJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_validates_health_status_for_active_users(): void
    {
        // Create user with recent file uploads (active user)
        $user = User::factory()->create();
        FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'created_at' => now()->subDays(3)
        ]);

        // Mock the health validator and service
        $mockValidator = Mockery::mock(RealTimeHealthValidator::class);
        $mockHealthService = Mockery::mock(CloudStorageHealthService::class);
        
        $healthStatus = new HealthStatus('healthy', true, 'Connection is working');
        
        $mockValidator->shouldReceive('validateConnectionHealth')
            ->once()
            ->with($user, 'google-drive')
            ->andReturn($healthStatus);
            
        $mockHealthService->shouldReceive('updateHealthStatus')
            ->once()
            ->with($user, 'google-drive', $healthStatus);

        $this->app->instance(RealTimeHealthValidator::class, $mockValidator);
        $this->app->instance(CloudStorageHealthService::class, $mockHealthService);

        $job = new HealthStatusValidationJob();
        $job->handle();

        // Assertions are handled by Mockery expectations
        $this->assertTrue(true);
    }

    public function test_validates_users_with_google_drive_tokens(): void
    {
        // Create user with Google Drive token but no recent uploads
        $user = User::factory()->create();
        GoogleDriveToken::factory()->create(['user_id' => $user->id]);

        // Mock the health validator and service
        $mockValidator = Mockery::mock(RealTimeHealthValidator::class);
        $mockHealthService = Mockery::mock(CloudStorageHealthService::class);
        
        $healthStatus = new HealthStatus('healthy', true, 'Connection is working');
        
        $mockValidator->shouldReceive('validateConnectionHealth')
            ->once()
            ->with($user, 'google-drive')
            ->andReturn($healthStatus);
            
        $mockHealthService->shouldReceive('updateHealthStatus')
            ->once()
            ->with($user, 'google-drive', $healthStatus);

        $this->app->instance(RealTimeHealthValidator::class, $mockValidator);
        $this->app->instance(CloudStorageHealthService::class, $mockHealthService);

        $job = new HealthStatusValidationJob();
        $job->handle();

        // Assertions are handled by Mockery expectations
        $this->assertTrue(true);
    }

    public function test_skips_inactive_users_without_tokens(): void
    {
        // Create user with old file uploads and no tokens
        $user = User::factory()->create();
        FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'created_at' => now()->subDays(10) // Older than 7 days
        ]);

        // Mock services - should not be called
        $mockValidator = Mockery::mock(RealTimeHealthValidator::class);
        $mockHealthService = Mockery::mock(CloudStorageHealthService::class);
        
        $mockValidator->shouldNotReceive('validateConnectionHealth');
        $mockHealthService->shouldNotReceive('updateHealthStatus');

        $this->app->instance(RealTimeHealthValidator::class, $mockValidator);
        $this->app->instance(CloudStorageHealthService::class, $mockHealthService);

        $job = new HealthStatusValidationJob();
        $job->handle();

        // Assertions are handled by Mockery expectations
        $this->assertTrue(true);
    }

    public function test_handles_validation_errors_gracefully(): void
    {
        // Create active user
        $user = User::factory()->create();
        FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'created_at' => now()->subDays(1)
        ]);

        // Mock validator to throw exception
        $mockValidator = Mockery::mock(RealTimeHealthValidator::class);
        $mockHealthService = Mockery::mock(CloudStorageHealthService::class);
        
        $mockValidator->shouldReceive('validateConnectionHealth')
            ->once()
            ->with($user, 'google-drive')
            ->andThrow(new \Exception('Validation failed'));
            
        // Health service should not be called when validation fails
        $mockHealthService->shouldNotReceive('updateHealthStatus');

        $this->app->instance(RealTimeHealthValidator::class, $mockValidator);
        $this->app->instance(CloudStorageHealthService::class, $mockHealthService);

        $job = new HealthStatusValidationJob();
        
        // Should not throw exception, should handle gracefully
        $job->handle();
        
        $this->assertTrue(true);
    }

    public function test_cleans_up_stale_health_records(): void
    {
        // Create old health status record for inactive user
        $user = User::factory()->create();
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $user->id,
            'updated_at' => now()->subDays(35) // Older than 30 days
        ]);

        // No recent file uploads for this user
        
        // Mock services (they may or may not be called depending on user activity)
        $mockValidator = Mockery::mock(RealTimeHealthValidator::class);
        $mockHealthService = Mockery::mock(CloudStorageHealthService::class);
        
        $mockValidator->shouldReceive('validateConnectionHealth')->zeroOrMoreTimes();
        $mockHealthService->shouldReceive('updateHealthStatus')->zeroOrMoreTimes();

        $this->app->instance(RealTimeHealthValidator::class, $mockValidator);
        $this->app->instance(CloudStorageHealthService::class, $mockHealthService);

        $job = new HealthStatusValidationJob();
        $job->handle();

        // Health record should be deleted
        $this->assertDatabaseMissing('cloud_storage_health_statuses', [
            'id' => $healthStatus->id
        ]);
    }

    public function test_preserves_health_records_for_active_users(): void
    {
        // Create old health status record for active user
        $user = User::factory()->create();
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $user->id,
            'updated_at' => now()->subDays(35) // Older than 30 days
        ]);

        // Recent file upload makes user active
        FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'created_at' => now()->subDays(5)
        ]);

        // Mock services
        $mockValidator = Mockery::mock(RealTimeHealthValidator::class);
        $mockHealthService = Mockery::mock(CloudStorageHealthService::class);
        
        $healthStatusObj = new HealthStatus('healthy', true, 'Connection is working');
        
        $mockValidator->shouldReceive('validateConnectionHealth')
            ->once()
            ->andReturn($healthStatusObj);
            
        $mockHealthService->shouldReceive('updateHealthStatus')
            ->once();

        $this->app->instance(RealTimeHealthValidator::class, $mockValidator);
        $this->app->instance(CloudStorageHealthService::class, $mockHealthService);

        $job = new HealthStatusValidationJob();
        $job->handle();

        // Health record should still exist (not deleted)
        $this->assertDatabaseHas('cloud_storage_health_statuses', [
            'id' => $healthStatus->id
        ]);
    }
}