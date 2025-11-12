<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\CloudStorageSetting;
use App\Models\User;
use App\Services\CloudStorageFactory;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CloudStorageS3ConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private CloudStorageSettingsService $settingsService;
    private CloudStorageHealthService $healthService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email_verified_at' => now()
        ]);

        $this->settingsService = app(CloudStorageSettingsService::class);
        $this->healthService = app(CloudStorageHealthService::class);

        // Ensure S3 provider is available in config
        Config::set('cloud-storage.providers.amazon-s3.availability', 'fully_available');
    }

    /**
     * Test admin can access S3 configuration page.
     * 
     * @test
     * Requirements: 1.1, 1.2, 9.1, 9.2
     */
    public function test_admin_can_access_s3_configuration_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.cloud-storage.index');
        
        // Should see Amazon S3 as an option
        $response->assertSee('Amazon S3');
        $response->assertSee('AWS Access Key ID');
        $response->assertSee('AWS Secret Access Key');
        $response->assertSee('AWS Region');
        $response->assertSee('S3 Bucket Name');
    }

    /**
     * Test admin can save valid S3 configuration.
     * 
     * @test
     * Requirements: 1.3, 1.4, 1.5, 6.1, 6.2, 6.3, 6.4
     */
    public function test_admin_can_save_valid_s3_configuration(): void
    {
        $validConfig = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'us-east-1',
            'aws_bucket' => 'test-bucket-name',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), $validConfig);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        
        // Verify configuration was saved to database
        $this->assertDatabaseHas('cloud_storage_settings', [
            'user_id' => null, // System-level
            'provider' => 'amazon-s3',
            'key' => 'access_key_id',
        ]);

        $this->assertDatabaseHas('cloud_storage_settings', [
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'bucket',
            'value' => 'test-bucket-name',
        ]);

        $this->assertDatabaseHas('cloud_storage_settings', [
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'region',
            'value' => 'us-east-1',
        ]);
    }

    /**
     * Test validation errors on invalid S3 configuration.
     * 
     * @test
     * Requirements: 6.1, 6.2, 6.3, 6.4, 6.5
     */
    public function test_validation_errors_on_invalid_configuration(): void
    {
        // Test invalid access key ID format (should be 20 uppercase alphanumeric)
        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), [
                'aws_access_key_id' => 'invalid-key',
                'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
                'aws_region' => 'us-east-1',
                'aws_bucket' => 'test-bucket',
            ]);

        $response->assertSessionHasErrors(['aws_access_key_id']);

        // Test invalid secret access key length (should be 40 characters)
        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), [
                'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
                'aws_secret_access_key' => 'too-short',
                'aws_region' => 'us-east-1',
                'aws_bucket' => 'test-bucket',
            ]);

        $response->assertSessionHasErrors(['aws_secret_access_key']);

        // Test invalid region format
        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), [
                'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
                'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
                'aws_region' => 'INVALID_REGION',
                'aws_bucket' => 'test-bucket',
            ]);

        $response->assertSessionHasErrors(['aws_region']);

        // Test invalid bucket name format
        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), [
                'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
                'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
                'aws_region' => 'us-east-1',
                'aws_bucket' => 'Invalid_Bucket_Name',
            ]);

        $response->assertSessionHasErrors(['aws_bucket']);
    }

    /**
     * Test S3 configuration requires all mandatory fields.
     * 
     * @test
     * Requirements: 6.1, 6.2, 6.3, 6.4
     */
    public function test_s3_configuration_requires_all_mandatory_fields(): void
    {
        // Missing access key ID
        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), [
                'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
                'aws_region' => 'us-east-1',
                'aws_bucket' => 'test-bucket',
            ]);

        $response->assertSessionHasErrors(['aws_access_key_id']);

        // Missing secret access key
        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), [
                'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
                'aws_region' => 'us-east-1',
                'aws_bucket' => 'test-bucket',
            ]);

        $response->assertSessionHasErrors(['aws_secret_access_key']);

        // Missing region
        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), [
                'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
                'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
                'aws_bucket' => 'test-bucket',
            ]);

        $response->assertSessionHasErrors(['aws_region']);

        // Missing bucket
        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), [
                'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
                'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
                'aws_region' => 'us-east-1',
            ]);

        $response->assertSessionHasErrors(['aws_bucket']);
    }

    /**
     * Test health check is performed after S3 configuration.
     * 
     * @test
     * Requirements: 1.5, 8.1, 8.2, 8.3, 8.4
     */
    public function test_health_check_after_configuration(): void
    {
        $validConfig = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'us-east-1',
            'aws_bucket' => 'test-bucket-name',
        ];

        // Mock the health check to avoid actual AWS API calls
        $this->mock(CloudStorageFactory::class, function ($mock) {
            $providerMock = \Mockery::mock(\App\Services\S3Provider::class);
            $healthStatusMock = \Mockery::mock(\App\Services\CloudStorageHealthStatus::class);
            
            $healthStatusMock->shouldReceive('isHealthy')
                ->andReturn(true);
            $healthStatusMock->last_error_message = null;
            $healthStatusMock->error_type = null;
            
            $providerMock->shouldReceive('getConnectionHealth')
                ->andReturn($healthStatusMock);
            
            $mock->shouldReceive('create')
                ->with('amazon-s3')
                ->andReturn($providerMock);
        });

        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), $validConfig);

        $response->assertRedirect();
        
        // Should have success message indicating connection was verified
        $response->assertSessionHas('success');
    }

    /**
     * Test S3 credentials are encrypted in database.
     * 
     * @test
     * Requirements: 1.4
     */
    public function test_credential_encryption_in_database(): void
    {
        $validConfig = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'us-east-1',
            'aws_bucket' => 'test-bucket-name',
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), $validConfig);

        // Check that access_key_id is NOT encrypted (only secret_access_key should be encrypted)
        $accessKeySetting = CloudStorageSetting::where('provider', 'amazon-s3')
            ->where('key', 'access_key_id')
            ->whereNull('user_id')
            ->first();

        $this->assertNotNull($accessKeySetting);
        $this->assertFalse($accessKeySetting->encrypted);
        $this->assertEquals($validConfig['aws_access_key_id'], $accessKeySetting->value);

        // Check that secret_access_key is encrypted
        $secretKeySetting = CloudStorageSetting::where('provider', 'amazon-s3')
            ->where('key', 'secret_access_key')
            ->whereNull('user_id')
            ->first();

        $this->assertNotNull($secretKeySetting);
        $this->assertTrue($secretKeySetting->encrypted);
        $this->assertNotEquals($validConfig['aws_secret_access_key'], $secretKeySetting->value);

        // Verify decryption works correctly
        $this->assertEquals($validConfig['aws_access_key_id'], $accessKeySetting->decrypted_value);
        $this->assertEquals($validConfig['aws_secret_access_key'], $secretKeySetting->decrypted_value);
    }

    /**
     * Test switching default provider to S3.
     * 
     * @test
     * Requirements: 1.1
     */
    public function test_switching_default_provider_to_s3(): void
    {
        // First configure S3
        $validConfig = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'us-east-1',
            'aws_bucket' => 'test-bucket-name',
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), $validConfig);

        // Now switch default provider to S3
        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.default'), [
                'default_provider' => 'amazon-s3',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
    }

    /**
     * Test S3 configuration with custom endpoint for S3-compatible services.
     * 
     * @test
     * Requirements: 14.1, 14.2, 14.4
     */
    public function test_s3_configuration_with_custom_endpoint(): void
    {
        $configWithEndpoint = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'auto',
            'aws_bucket' => 'test-bucket',
            'aws_endpoint' => 'https://s3.cloudflare.com',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), $configWithEndpoint);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Verify custom endpoint was saved
        $this->assertDatabaseHas('cloud_storage_settings', [
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'endpoint',
            'value' => 'https://s3.cloudflare.com',
        ]);
    }

    /**
     * Test S3 configuration validates custom endpoint URL format.
     * 
     * @test
     * Requirements: 14.4
     */
    public function test_s3_configuration_validates_custom_endpoint_url(): void
    {
        $configWithInvalidEndpoint = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'us-east-1',
            'aws_bucket' => 'test-bucket',
            'aws_endpoint' => 'not-a-valid-url',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), $configWithInvalidEndpoint);

        $response->assertSessionHasErrors(['aws_endpoint']);
    }

    /**
     * Test S3 connection test endpoint without saving configuration.
     * 
     * @test
     * Requirements: 1.5, 8.1, 8.2, 8.3, 8.4
     */
    public function test_s3_connection_test_without_saving(): void
    {
        $testConfig = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'us-east-1',
            'aws_bucket' => 'test-bucket',
        ];

        // Mock the factory to avoid actual AWS API calls
        $this->mock(CloudStorageFactory::class, function ($mock) {
            $providerMock = \Mockery::mock(\App\Services\S3Provider::class);
            $healthStatusMock = \Mockery::mock(\App\Services\CloudStorageHealthStatus::class);
            
            $healthStatusMock->shouldReceive('isHealthy')
                ->andReturn(true);
            $healthStatusMock->last_error_message = null;
            $healthStatusMock->error_type = null;
            
            $providerMock->shouldReceive('getConnectionHealth')
                ->andReturn($healthStatusMock);
            
            $mock->shouldReceive('create')
                ->with('amazon-s3', \Mockery::any())
                ->andReturn($providerMock);
        });

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.cloud-storage.amazon-s3.test-connection'), $testConfig);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'healthy',
        ]);

        // Verify configuration was NOT saved to database
        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'access_key_id',
        ]);
    }

    /**
     * Test S3 disconnect functionality.
     * 
     * @test
     * Requirements: 9.5, 12.5
     */
    public function test_s3_disconnect_functionality(): void
    {
        // First configure S3
        $validConfig = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'us-east-1',
            'aws_bucket' => 'test-bucket-name',
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), $validConfig);

        // Verify configuration exists
        $this->assertDatabaseHas('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'access_key_id',
        ]);

        // Now disconnect
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.cloud-storage.amazon-s3.disconnect'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify configuration was removed
        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'access_key_id',
        ]);

        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'secret_access_key',
        ]);
    }

    /**
     * Test non-admin users cannot access S3 configuration.
     * 
     * @test
     * Requirements: 1.1, 1.2
     */
    public function test_non_admin_cannot_access_s3_configuration(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email_verified_at' => now()
        ]);

        $response = $this->actingAs($employee)
            ->get(route('admin.cloud-storage.index'));

        // Should be forbidden or redirected
        $this->assertTrue(
            $response->status() === 403 || $response->isRedirect()
        );
    }

    /**
     * Test S3 configuration handles connection failures gracefully.
     * 
     * @test
     * Requirements: 1.5, 8.3, 8.4
     */
    public function test_s3_configuration_handles_connection_failures(): void
    {
        $validConfig = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'us-east-1',
            'aws_bucket' => 'test-bucket-name',
        ];

        // Mock the health check to simulate connection failure
        $this->mock(CloudStorageFactory::class, function ($mock) {
            $providerMock = \Mockery::mock(\App\Services\S3Provider::class);
            $healthStatusMock = \Mockery::mock(\App\Services\CloudStorageHealthStatus::class);
            
            $healthStatusMock->shouldReceive('isHealthy')
                ->andReturn(false);
            $healthStatusMock->last_error_message = 'Invalid credentials';
            $healthStatusMock->error_type = 'invalid_credentials';
            
            $providerMock->shouldReceive('getConnectionHealth')
                ->andReturn($healthStatusMock);
            
            $mock->shouldReceive('create')
                ->with('amazon-s3')
                ->andReturn($providerMock);
        });

        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), $validConfig);

        $response->assertRedirect();
        
        // Should have warning message indicating connection failed
        $response->assertSessionHas('warning');
        
        // Configuration should still be saved even if health check fails
        $this->assertDatabaseHas('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'bucket',
        ]);
    }

    /**
     * Test S3 configuration with storage class option.
     * 
     * @test
     * Requirements: 10.2, 10.5
     */
    public function test_s3_configuration_with_storage_class(): void
    {
        $configWithStorageClass = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'us-east-1',
            'aws_bucket' => 'test-bucket',
            'aws_storage_class' => 'INTELLIGENT_TIERING',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), $configWithStorageClass);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Verify storage class was saved
        $this->assertDatabaseHas('cloud_storage_settings', [
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'storage_class',
            'value' => 'INTELLIGENT_TIERING',
        ]);
    }

    /**
     * Test S3 configuration validates storage class values.
     * 
     * @test
     * Requirements: 10.2
     */
    public function test_s3_configuration_validates_storage_class(): void
    {
        $configWithInvalidStorageClass = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'us-east-1',
            'aws_bucket' => 'test-bucket',
            'aws_storage_class' => 'INVALID_CLASS',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), $configWithInvalidStorageClass);

        $response->assertSessionHasErrors(['aws_storage_class']);
    }
}
