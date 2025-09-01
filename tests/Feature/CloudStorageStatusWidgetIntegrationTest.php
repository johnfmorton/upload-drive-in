<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\FileUpload;
use App\Models\CloudStorageHealthStatus;
use App\Services\CloudStorageHealthService;
use App\Enums\CloudStorageErrorType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class CloudStorageStatusWidgetIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->employee = User::factory()->create([
            'role' => 'employee',
            'username' => 'testemployee'
        ]);
    }

    /** @test */
    public function admin_can_get_cloud_storage_status()
    {
        $this->actingAs($this->admin);

        // Create health status
        CloudStorageHealthStatus::create([
            'user_id' => $this->admin->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'last_successful_operation_at' => now()->subMinutes(5),
        ]);

        // Create pending and failed uploads
        FileUpload::factory()->count(2)->create([
            'company_user_id' => $this->admin->id,
            'cloud_storage_provider' => 'google-drive',
            'google_drive_file_id' => null,
            'cloud_storage_error_type' => null,
        ]);

        FileUpload::factory()->create([
            'company_user_id' => $this->admin->id,
            'cloud_storage_provider' => 'google-drive',
            'cloud_storage_error_type' => CloudStorageErrorType::TOKEN_EXPIRED->value,
        ]);

        $response = $this->getJson(route('admin.cloud-storage.status'));

        $response->assertOk()
            ->assertJsonStructure([
                'providers' => [
                    '*' => [
                        'provider',
                        'status',
                        'status_message',
                        'is_healthy',
                        'is_degraded',
                        'is_unhealthy',
                        'is_disconnected',
                        'last_successful_operation',
                        'consecutive_failures',
                        'requires_reconnection',
                    ]
                ],
                'pending_uploads',
                'failed_uploads'
            ])
            ->assertJson([
                'pending_uploads' => ['google-drive' => 2],
                'failed_uploads' => ['google-drive' => 1]
            ]);
    }

    /** @test */
    public function employee_can_get_cloud_storage_status()
    {
        $this->actingAs($this->employee);

        CloudStorageHealthStatus::create([
            'user_id' => $this->employee->id,
            'provider' => 'google-drive',
            'status' => 'degraded',
            'consecutive_failures' => 2,
        ]);

        $response = $this->getJson(route('employee.cloud-storage.status', ['username' => $this->employee->username]));

        $response->assertOk()
            ->assertJsonFragment([
                'provider' => 'google-drive',
                'status' => 'degraded',
                'consecutive_failures' => 2
            ]);
    }

    /** @test */
    public function admin_can_reconnect_google_drive_provider()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.cloud-storage.reconnect'), [
            'provider' => 'google-drive'
        ]);

        $response->assertOk()
            ->assertJsonStructure(['redirect_url']);
        
        // Verify the redirect URL is a valid Google OAuth URL
        $data = $response->json();
        $this->assertStringContainsString('accounts.google.com', $data['redirect_url']);
    }

    /** @test */
    public function employee_can_reconnect_google_drive_provider()
    {
        $this->actingAs($this->employee);

        $response = $this->postJson(route('employee.cloud-storage.reconnect', ['username' => $this->employee->username]), [
            'provider' => 'google-drive'
        ]);

        $response->assertOk()
            ->assertJsonStructure(['redirect_url']);
    }

    /** @test */
    public function admin_can_test_connection()
    {
        $this->actingAs($this->admin);

        CloudStorageHealthStatus::create([
            'user_id' => $this->admin->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
        ]);

        $response = $this->postJson(route('admin.cloud-storage.test'), [
            'provider' => 'google-drive'
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'status'
            ]);
    }

    /** @test */
    public function admin_can_retry_failed_uploads()
    {
        $this->actingAs($this->admin);
        Queue::fake();

        // Create failed uploads
        $failedUploads = FileUpload::factory()->count(3)->create([
            'company_user_id' => $this->admin->id,
            'cloud_storage_provider' => 'google-drive',
            'cloud_storage_error_type' => CloudStorageErrorType::NETWORK_ERROR->value,
        ]);

        $response = $this->postJson(route('admin.files.retry-failed'), [
            'provider' => 'google-drive'
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'retried_count' => 3
            ]);

        // Verify error fields were cleared
        foreach ($failedUploads as $upload) {
            $upload->refresh();
            $this->assertNull($upload->cloud_storage_error_type);
            $this->assertNull($upload->cloud_storage_error_context);
        }

        // Verify jobs were dispatched
        Queue::assertPushed(\App\Jobs\UploadToGoogleDrive::class, 3);
    }

    /** @test */
    public function employee_can_retry_failed_uploads()
    {
        $this->actingAs($this->employee);
        Queue::fake();

        FileUpload::factory()->count(2)->create([
            'company_user_id' => $this->employee->id,
            'cloud_storage_provider' => 'google-drive',
            'cloud_storage_error_type' => CloudStorageErrorType::API_QUOTA_EXCEEDED->value,
        ]);

        $response = $this->postJson(route('employee.files.retry-failed', ['username' => $this->employee->username]), [
            'provider' => 'google-drive'
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'retried_count' => 2
            ]);

        Queue::assertPushed(\App\Jobs\UploadToGoogleDrive::class, 2);
    }

    /** @test */
    public function retry_without_provider_retries_all_failed_uploads()
    {
        $this->actingAs($this->admin);
        Queue::fake();

        // Create failed uploads for different providers
        FileUpload::factory()->create([
            'company_user_id' => $this->admin->id,
            'cloud_storage_provider' => 'google-drive',
            'cloud_storage_error_type' => CloudStorageErrorType::TOKEN_EXPIRED->value,
        ]);

        FileUpload::factory()->create([
            'company_user_id' => $this->admin->id,
            'cloud_storage_provider' => 'dropbox',
            'cloud_storage_error_type' => CloudStorageErrorType::NETWORK_ERROR->value,
        ]);

        $response = $this->postJson(route('admin.files.retry-failed'));

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'retried_count' => 2
            ]);

        Queue::assertPushed(\App\Jobs\UploadToGoogleDrive::class, 2);
    }

    /** @test */
    public function retry_with_no_failed_uploads_returns_appropriate_message()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.files.retry-failed'), [
            'provider' => 'google-drive'
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'retried_count' => 0,
                'message' => 'No failed uploads found for google-drive.'
            ]);
    }

    /** @test */
    public function reconnect_validates_provider_parameter()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.cloud-storage.reconnect'), [
            'provider' => 'invalid-provider'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['provider']);
    }

    /** @test */
    public function test_connection_validates_provider_parameter()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.cloud-storage.test'), [
            'provider' => 'invalid-provider'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['provider']);
    }

    /** @test */
    public function retry_validates_provider_parameter()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.files.retry-failed'), [
            'provider' => 'invalid-provider'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['provider']);
    }

    /** @test */
    public function unauthenticated_users_cannot_access_endpoints()
    {
        $response = $this->getJson(route('admin.cloud-storage.status'));
        $response->assertStatus(401);

        $response = $this->postJson(route('admin.cloud-storage.reconnect'), ['provider' => 'google-drive']);
        $response->assertStatus(401);

        $response = $this->postJson(route('admin.cloud-storage.test'), ['provider' => 'google-drive']);
        $response->assertStatus(401);

        $response = $this->postJson(route('admin.files.retry-failed'));
        $response->assertStatus(401);
    }

    /** @test */
    public function non_admin_users_cannot_access_admin_endpoints()
    {
        $client = User::factory()->create(['role' => 'client']);
        $this->actingAs($client);

        $response = $this->getJson(route('admin.cloud-storage.status'));
        $response->assertStatus(403);

        $response = $this->postJson(route('admin.cloud-storage.reconnect'), ['provider' => 'google-drive']);
        $response->assertStatus(403);
    }

    /** @test */
    public function employee_cannot_access_other_employee_endpoints()
    {
        $otherEmployee = User::factory()->create(['role' => 'employee', 'username' => 'other']);
        $this->actingAs($this->employee);

        $response = $this->getJson(route('employee.cloud-storage.status', ['username' => 'other']));
        $response->assertStatus(403);
    }

    /** @test */
    public function dashboard_widget_renders_correctly_for_admin()
    {
        $this->actingAs($this->admin);

        CloudStorageHealthStatus::create([
            'user_id' => $this->admin->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
        ]);

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Cloud Storage Status')
            ->assertSee('Monitor your cloud storage connections')
            ->assertSee('Google Drive')
            ->assertSee('Healthy');
    }

    /** @test */
    public function dashboard_widget_renders_correctly_for_employee()
    {
        $this->actingAs($this->employee);

        CloudStorageHealthStatus::create([
            'user_id' => $this->employee->id,
            'provider' => 'google-drive',
            'status' => 'degraded',
            'consecutive_failures' => 1,
        ]);

        $response = $this->get(route('employee.dashboard', ['username' => $this->employee->username]));

        $response->assertOk()
            ->assertSee('Cloud Storage Status')
            ->assertSee('Google Drive')
            ->assertSee('Degraded');
    }

    /** @test */
    public function widget_handles_real_time_updates()
    {
        $this->actingAs($this->admin);

        // Initial status
        $healthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->admin->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
        ]);

        $response = $this->getJson(route('admin.cloud-storage.status'));
        $response->assertJsonFragment(['status' => 'healthy']);

        // Update status
        $healthStatus->update(['status' => 'degraded', 'consecutive_failures' => 1]);

        $response = $this->getJson(route('admin.cloud-storage.status'));
        $response->assertJsonFragment(['status' => 'degraded', 'consecutive_failures' => 1]);
    }

    /** @test */
    public function widget_displays_provider_specific_data()
    {
        $this->actingAs($this->admin);

        CloudStorageHealthStatus::create([
            'user_id' => $this->admin->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'provider_specific_data' => [
                'quota_used' => '50GB',
                'quota_total' => '100GB'
            ],
        ]);

        $response = $this->getJson(route('admin.cloud-storage.status'));

        $response->assertOk()
            ->assertJsonPath('providers.0.provider_specific_data.quota_used', '50GB')
            ->assertJsonPath('providers.0.provider_specific_data.quota_total', '100GB');
    }
}