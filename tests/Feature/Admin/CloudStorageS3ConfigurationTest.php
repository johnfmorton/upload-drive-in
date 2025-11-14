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

        // Missing secret access key (when no existing configuration exists yet in this test)
        // Note: Secret key is required when there's no existing configuration
        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), [
                'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
                'aws_region' => 'us-east-1',
                'aws_bucket' => 'test-bucket',
            ]);

        // Check if validation failed (302 redirect with errors)
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

    /**
     * Test mixed configuration with only access key in environment.
     * 
     * @test
     * Requirements: 2.1, 4.2
     */
    public function test_mixed_configuration_with_only_access_key_in_environment(): void
    {
        // Set only access key in environment
        Config::set('filesystems.disks.s3.key', 'AKIAIOSFODNN7EXAMPLE');
        putenv('AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        
        // Verify the view receives correct environment settings
        $response->assertViewHas('s3EnvSettings', function ($settings) {
            return $settings['access_key_id'] === true &&
                   $settings['secret_access_key'] === false &&
                   $settings['region'] === false &&
                   $settings['bucket'] === false &&
                   $settings['endpoint'] === false;
        });

        // Verify access key field should be read-only
        $response->assertSee('AWS_ACCESS_KEY_ID');
        $response->assertSee('This value is configured via environment variables');
        
        // Verify save button should be visible (other fields are editable)
        $response->assertSee('save_configuration');

        // Clean up
        putenv('AWS_ACCESS_KEY_ID');
    }

    /**
     * Test mixed configuration with only secret key in environment.
     * 
     * @test
     * Requirements: 2.2, 4.2
     */
    public function test_mixed_configuration_with_only_secret_key_in_environment(): void
    {
        // Set only secret key in environment
        Config::set('filesystems.disks.s3.secret', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');
        putenv('AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        
        // Verify the view receives correct environment settings
        $response->assertViewHas('s3EnvSettings', function ($settings) {
            return $settings['access_key_id'] === false &&
                   $settings['secret_access_key'] === true &&
                   $settings['region'] === false &&
                   $settings['bucket'] === false &&
                   $settings['endpoint'] === false;
        });

        // Verify secret key field should be read-only with masked display
        $response->assertSee('••••••••••••••••••••••••••••••••••••••••');
        $response->assertSee('This value is configured via environment variables');
        
        // Verify save button should be visible (other fields are editable)
        $response->assertSee('save_configuration');

        // Clean up
        putenv('AWS_SECRET_ACCESS_KEY');
    }

    /**
     * Test mixed configuration with access key and secret in environment, region and bucket in database.
     * 
     * @test
     * Requirements: 2.1, 2.2, 2.3, 2.4, 4.2
     */
    public function test_mixed_configuration_with_credentials_in_env_and_settings_in_database(): void
    {
        // Set credentials in environment
        Config::set('filesystems.disks.s3.key', 'AKIAIOSFODNN7EXAMPLE');
        Config::set('filesystems.disks.s3.secret', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');
        putenv('AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE');
        putenv('AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');

        // Save region and bucket to database
        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'region',
            'value' => 'us-west-2',
            'encrypted' => false,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'bucket',
            'value' => 'my-test-bucket',
            'encrypted' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        
        // Verify the view receives correct environment settings
        $response->assertViewHas('s3EnvSettings', function ($settings) {
            return $settings['access_key_id'] === true &&
                   $settings['secret_access_key'] === true &&
                   $settings['region'] === false &&
                   $settings['bucket'] === false &&
                   $settings['endpoint'] === false;
        });

        // Verify credentials are read-only
        $response->assertSee('AKIAIOSFODNN7EXAMPLE');
        $response->assertSee('••••••••••••••••••••••••••••••••••••••••');
        
        // Verify region and bucket are editable (from database)
        $response->assertSee('us-west-2');
        $response->assertSee('my-test-bucket');
        
        // Verify save button should be visible (region and bucket are editable)
        $response->assertSee('save_configuration');

        // Clean up
        putenv('AWS_ACCESS_KEY_ID');
        putenv('AWS_SECRET_ACCESS_KEY');
    }

    /**
     * Test mixed configuration with all required fields in environment hides save button.
     * 
     * @test
     * Requirements: 2.1, 2.2, 2.3, 2.4, 4.2
     */
    public function test_mixed_configuration_with_all_required_fields_in_environment_hides_save_button(): void
    {
        // Set all required fields in environment
        Config::set('filesystems.disks.s3.key', 'AKIAIOSFODNN7EXAMPLE');
        Config::set('filesystems.disks.s3.secret', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');
        Config::set('filesystems.disks.s3.region', 'us-east-1');
        Config::set('filesystems.disks.s3.bucket', 'my-bucket');
        putenv('AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE');
        putenv('AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');
        putenv('AWS_DEFAULT_REGION=us-east-1');
        putenv('AWS_BUCKET=my-bucket');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        
        // Verify the view receives correct environment settings
        $response->assertViewHas('s3EnvSettings', function ($settings) {
            return $settings['access_key_id'] === true &&
                   $settings['secret_access_key'] === true &&
                   $settings['region'] === true &&
                   $settings['bucket'] === true;
        });

        // Verify all fields are read-only
        $response->assertSee('AKIAIOSFODNN7EXAMPLE');
        $response->assertSee('••••••••••••••••••••••••••••••••••••••••');
        $response->assertSee('us-east-1');
        $response->assertSee('my-bucket');
        
        // Verify save button should NOT be visible (all required fields from environment)
        // The save button should be hidden when all required fields are from environment
        $html = $response->getContent();
        $this->assertStringNotContainsString('type="submit"', $html);

        // Clean up
        putenv('AWS_ACCESS_KEY_ID');
        putenv('AWS_SECRET_ACCESS_KEY');
        putenv('AWS_DEFAULT_REGION');
        putenv('AWS_BUCKET');
    }

    /**
     * Test mixed configuration with region in environment and other fields in database.
     * 
     * @test
     * Requirements: 2.3, 4.2
     */
    public function test_mixed_configuration_with_region_in_environment(): void
    {
        // Set only region in environment
        Config::set('filesystems.disks.s3.region', 'eu-west-1');
        putenv('AWS_DEFAULT_REGION=eu-west-1');

        // Save other fields to database
        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'access_key_id',
            'value' => 'AKIAIOSFODNN7EXAMPLE',
            'encrypted' => false,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'secret_access_key',
            'value' => encrypt('wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY'),
            'encrypted' => true,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'bucket',
            'value' => 'test-bucket',
            'encrypted' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        
        // Verify the view receives correct environment settings
        $response->assertViewHas('s3EnvSettings', function ($settings) {
            return $settings['access_key_id'] === false &&
                   $settings['secret_access_key'] === false &&
                   $settings['region'] === true &&
                   $settings['bucket'] === false &&
                   $settings['endpoint'] === false;
        });

        // Verify region field is read-only
        $response->assertSee('eu-west-1');
        $response->assertSee('This value is configured via environment variables');
        
        // Verify save button should be visible (other fields are editable)
        $response->assertSee('save_configuration');

        // Clean up
        putenv('AWS_DEFAULT_REGION');
    }

    /**
     * Test mixed configuration with bucket in environment and other fields in database.
     * 
     * @test
     * Requirements: 2.4, 4.2
     */
    public function test_mixed_configuration_with_bucket_in_environment(): void
    {
        // Set only bucket in environment
        Config::set('filesystems.disks.s3.bucket', 'env-bucket-name');
        putenv('AWS_BUCKET=env-bucket-name');

        // Save other fields to database
        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'access_key_id',
            'value' => 'AKIAIOSFODNN7EXAMPLE',
            'encrypted' => false,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'secret_access_key',
            'value' => encrypt('wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY'),
            'encrypted' => true,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'region',
            'value' => 'us-east-1',
            'encrypted' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        
        // Verify the view receives correct environment settings
        $response->assertViewHas('s3EnvSettings', function ($settings) {
            return $settings['access_key_id'] === false &&
                   $settings['secret_access_key'] === false &&
                   $settings['region'] === false &&
                   $settings['bucket'] === true &&
                   $settings['endpoint'] === false;
        });

        // Verify bucket field is read-only
        $response->assertSee('env-bucket-name');
        $response->assertSee('This value is configured via environment variables');
        
        // Verify save button should be visible (other fields are editable)
        $response->assertSee('save_configuration');

        // Clean up
        putenv('AWS_BUCKET');
    }

    /**
     * Test mixed configuration with custom endpoint in environment.
     * 
     * @test
     * Requirements: 2.5, 4.2
     */
    public function test_mixed_configuration_with_endpoint_in_environment(): void
    {
        // Set endpoint in environment
        Config::set('filesystems.disks.s3.endpoint', 'https://s3.cloudflare.com');
        putenv('AWS_ENDPOINT=https://s3.cloudflare.com');

        // Save other fields to database
        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'access_key_id',
            'value' => 'AKIAIOSFODNN7EXAMPLE',
            'encrypted' => false,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'secret_access_key',
            'value' => encrypt('wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY'),
            'encrypted' => true,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'region',
            'value' => 'auto',
            'encrypted' => false,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'bucket',
            'value' => 'test-bucket',
            'encrypted' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        
        // Verify the view receives correct environment settings
        $response->assertViewHas('s3EnvSettings', function ($settings) {
            return $settings['access_key_id'] === false &&
                   $settings['secret_access_key'] === false &&
                   $settings['region'] === false &&
                   $settings['bucket'] === false &&
                   $settings['endpoint'] === true;
        });

        // Verify endpoint field is read-only
        $response->assertSee('https://s3.cloudflare.com');
        $response->assertSee('This value is configured via environment variables');
        
        // Verify save button should be visible (other fields are editable)
        $response->assertSee('save_configuration');

        // Clean up
        putenv('AWS_ENDPOINT');
    }

    /**
     * Test connection with all credentials from environment.
     * 
     * @test
     * Requirements: 6.1, 6.3
     */
    public function test_connection_with_all_credentials_from_environment(): void
    {
        // Set all credentials in environment
        putenv('AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE');
        putenv('AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');
        putenv('AWS_DEFAULT_REGION=us-east-1');
        putenv('AWS_BUCKET=env-test-bucket');

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
                ->with('amazon-s3', \Mockery::on(function ($config) {
                    // Verify the test uses environment values
                    return $config['access_key_id'] === 'AKIAIOSFODNN7EXAMPLE' &&
                           $config['secret_access_key'] === 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY' &&
                           $config['region'] === 'us-east-1' &&
                           $config['bucket'] === 'env-test-bucket';
                }))
                ->andReturn($providerMock);
        });

        // Test connection with environment credentials
        $testConfig = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'us-east-1',
            'aws_bucket' => 'env-test-bucket',
        ];

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

        // Clean up
        putenv('AWS_ACCESS_KEY_ID');
        putenv('AWS_SECRET_ACCESS_KEY');
        putenv('AWS_DEFAULT_REGION');
        putenv('AWS_BUCKET');
    }

    /**
     * Test connection with all credentials from database.
     * 
     * @test
     * Requirements: 6.1, 6.3
     */
    public function test_connection_with_all_credentials_from_database(): void
    {
        // Save all credentials to database
        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'access_key_id',
            'value' => 'AKIAIOSFODNN7EXAMPLE',
            'encrypted' => false,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'secret_access_key',
            'value' => encrypt('wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY'),
            'encrypted' => true,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'region',
            'value' => 'us-west-2',
            'encrypted' => false,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'bucket',
            'value' => 'db-test-bucket',
            'encrypted' => false,
        ]);

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
                ->with('amazon-s3', \Mockery::on(function ($config) {
                    // Verify the test uses database values
                    return $config['access_key_id'] === 'AKIAIOSFODNN7EXAMPLE' &&
                           $config['secret_access_key'] === 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY' &&
                           $config['region'] === 'us-west-2' &&
                           $config['bucket'] === 'db-test-bucket';
                }))
                ->andReturn($providerMock);
        });

        // Test connection with database credentials
        $testConfig = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'us-west-2',
            'aws_bucket' => 'db-test-bucket',
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.cloud-storage.amazon-s3.test-connection'), $testConfig);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'healthy',
        ]);

        // Verify existing database configuration was not modified
        $this->assertDatabaseHas('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'bucket',
            'value' => 'db-test-bucket',
        ]);
    }

    /**
     * Test connection with mixed credentials (environment and database).
     * 
     * @test
     * Requirements: 6.1, 6.3
     */
    public function test_connection_with_mixed_credentials(): void
    {
        // Set credentials in environment
        putenv('AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE');
        putenv('AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');

        // Save region and bucket to database
        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'region',
            'value' => 'eu-central-1',
            'encrypted' => false,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'bucket',
            'value' => 'mixed-test-bucket',
            'encrypted' => false,
        ]);

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
                ->with('amazon-s3', \Mockery::on(function ($config) {
                    // Verify the test uses mixed values (env credentials + db settings)
                    return $config['access_key_id'] === 'AKIAIOSFODNN7EXAMPLE' &&
                           $config['secret_access_key'] === 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY' &&
                           $config['region'] === 'eu-central-1' &&
                           $config['bucket'] === 'mixed-test-bucket';
                }))
                ->andReturn($providerMock);
        });

        // Test connection with mixed credentials
        $testConfig = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'eu-central-1',
            'aws_bucket' => 'mixed-test-bucket',
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.cloud-storage.amazon-s3.test-connection'), $testConfig);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'healthy',
        ]);

        // Verify database configuration was not modified
        $this->assertDatabaseHas('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'region',
            'value' => 'eu-central-1',
        ]);

        // Clean up
        putenv('AWS_ACCESS_KEY_ID');
        putenv('AWS_SECRET_ACCESS_KEY');
    }

    /**
     * Test connection uses correct credential source.
     * 
     * @test
     * Requirements: 6.1, 6.3
     */
    public function test_connection_uses_correct_credential_source(): void
    {
        // Set some credentials in environment
        putenv('AWS_ACCESS_KEY_ID=AKIAENVEXAMPLE123456');
        putenv('AWS_SECRET_ACCESS_KEY=envSecretKey1234567890123456789012345678');

        // Save different credentials to database
        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'access_key_id',
            'value' => 'AKIADBEXAMPLE1234567',
            'encrypted' => false,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'secret_access_key',
            'value' => encrypt('dbSecretKey12345678901234567890123456789'),
            'encrypted' => true,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'region',
            'value' => 'ap-southeast-1',
            'encrypted' => false,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'bucket',
            'value' => 'source-test-bucket',
            'encrypted' => false,
        ]);

        // Mock the factory to verify correct credentials are used
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
                ->with('amazon-s3', \Mockery::on(function ($config) {
                    // Verify the test uses the credentials passed in the request
                    // (which should be from environment for access_key_id and secret_access_key)
                    return $config['access_key_id'] === 'AKIAENVEXAMPLE123456' &&
                           $config['secret_access_key'] === 'envSecretKey1234567890123456789012345678' &&
                           $config['region'] === 'ap-southeast-1' &&
                           $config['bucket'] === 'source-test-bucket';
                }))
                ->andReturn($providerMock);
        });

        // Test connection with environment credentials (should use env values)
        $testConfig = [
            'aws_access_key_id' => 'AKIAENVEXAMPLE123456',
            'aws_secret_access_key' => 'envSecretKey1234567890123456789012345678',
            'aws_region' => 'ap-southeast-1',
            'aws_bucket' => 'source-test-bucket',
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.cloud-storage.amazon-s3.test-connection'), $testConfig);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'healthy',
        ]);

        // Clean up
        putenv('AWS_ACCESS_KEY_ID');
        putenv('AWS_SECRET_ACCESS_KEY');
    }

    /**
     * Test connection results display correctly on success.
     * 
     * @test
     * Requirements: 6.1, 6.3
     */
    public function test_connection_results_display_correctly_on_success(): void
    {
        // Mock the factory to simulate successful connection
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

        $testConfig = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'us-east-1',
            'aws_bucket' => 'success-test-bucket',
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.cloud-storage.amazon-s3.test-connection'), $testConfig);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'healthy',
            'details' => [
                'bucket' => 'success-test-bucket',
                'region' => 'us-east-1',
                'has_custom_endpoint' => false,
            ],
        ]);

        // Verify the response includes a success message
        $response->assertJsonStructure([
            'success',
            'message',
            'status',
            'details' => [
                'bucket',
                'region',
                'has_custom_endpoint',
                'tested_at',
            ],
        ]);
    }

    /**
     * Test connection results display correctly on failure.
     * 
     * @test
     * Requirements: 6.1, 6.3
     */
    public function test_connection_results_display_correctly_on_failure(): void
    {
        // Mock the factory to simulate failed connection
        $this->mock(CloudStorageFactory::class, function ($mock) {
            $providerMock = \Mockery::mock(\App\Services\S3Provider::class);
            $healthStatusMock = \Mockery::mock(\App\Services\CloudStorageHealthStatus::class);
            
            $healthStatusMock->shouldReceive('isHealthy')
                ->andReturn(false);
            $healthStatusMock->last_error_message = 'Invalid credentials provided';
            $healthStatusMock->error_type = 'invalid_credentials';
            
            $providerMock->shouldReceive('getConnectionHealth')
                ->andReturn($healthStatusMock);
            
            $mock->shouldReceive('create')
                ->with('amazon-s3', \Mockery::any())
                ->andReturn($providerMock);
        });

        $testConfig = [
            'aws_access_key_id' => 'AKIAINVALIDEXAMPLE12',
            'aws_secret_access_key' => 'invalidSecretKey123456789012345678901234',
            'aws_region' => 'us-east-1',
            'aws_bucket' => 'failure-test-bucket',
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.cloud-storage.amazon-s3.test-connection'), $testConfig);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'status' => 'unhealthy',
            'error_type' => 'invalid_credentials',
            'error_message' => 'Invalid credentials provided',
        ]);

        // Verify the response includes error details
        $response->assertJsonStructure([
            'success',
            'message',
            'status',
            'error_type',
            'error_message',
            'is_retryable',
            'details' => [
                'bucket',
                'region',
                'has_custom_endpoint',
                'tested_at',
            ],
        ]);
    }

    /**
     * Test connection with custom endpoint.
     * 
     * @test
     * Requirements: 6.1, 6.3
     */
    public function test_connection_with_custom_endpoint(): void
    {
        // Mock the factory to avoid actual API calls
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
                ->with('amazon-s3', \Mockery::on(function ($config) {
                    // Verify custom endpoint is included
                    return isset($config['endpoint']) && 
                           $config['endpoint'] === 'https://s3.cloudflare.com';
                }))
                ->andReturn($providerMock);
        });

        $testConfig = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'auto',
            'aws_bucket' => 'cloudflare-bucket',
            'aws_endpoint' => 'https://s3.cloudflare.com',
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.cloud-storage.amazon-s3.test-connection'), $testConfig);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'healthy',
            'details' => [
                'bucket' => 'cloudflare-bucket',
                'region' => 'auto',
                'has_custom_endpoint' => true,
            ],
        ]);
    }

    /**
     * Test disconnect with database credentials.
     * 
     * @test
     * Requirements: 6.2, 6.4
     */
    public function test_disconnect_with_database_credentials(): void
    {
        // First configure S3 with database credentials
        $validConfig = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'us-east-1',
            'aws_bucket' => 'test-bucket-name',
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), $validConfig);

        // Verify configuration exists in database
        $this->assertDatabaseHas('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'access_key_id',
        ]);

        $this->assertDatabaseHas('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'secret_access_key',
        ]);

        $this->assertDatabaseHas('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'region',
        ]);

        $this->assertDatabaseHas('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'bucket',
        ]);

        // Now disconnect
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.cloud-storage.amazon-s3.disconnect'));

        $response->assertRedirect();
        $response->assertSessionHas('success', __('messages.s3_disconnected_successfully'));

        // Verify all database credentials were removed
        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'access_key_id',
        ]);

        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'secret_access_key',
        ]);

        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'region',
        ]);

        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'bucket',
        ]);
    }

    /**
     * Test disconnect with environment credentials does not modify environment variables.
     * 
     * @test
     * Requirements: 6.2, 6.4
     */
    public function test_disconnect_with_environment_credentials_does_not_modify_env(): void
    {
        // Set credentials in config (simulating environment variables)
        Config::set('filesystems.disks.s3.key', 'AKIAIOSFODNN7EXAMPLE');
        Config::set('filesystems.disks.s3.secret', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');
        Config::set('filesystems.disks.s3.region', 'us-east-1');
        Config::set('filesystems.disks.s3.bucket', 'env-test-bucket');

        // Verify no database credentials exist before disconnect
        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
        ]);

        // Disconnect
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.cloud-storage.amazon-s3.disconnect'));

        $response->assertRedirect();
        $response->assertSessionHas('success', __('messages.s3_disconnected_successfully'));

        // Verify config values are still set (not modified by disconnect)
        $this->assertEquals('AKIAIOSFODNN7EXAMPLE', Config::get('filesystems.disks.s3.key'));
        $this->assertEquals('wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY', Config::get('filesystems.disks.s3.secret'));
        $this->assertEquals('us-east-1', Config::get('filesystems.disks.s3.region'));
        $this->assertEquals('env-test-bucket', Config::get('filesystems.disks.s3.bucket'));

        // Verify no database credentials exist (disconnect doesn't create any)
        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
        ]);
    }

    /**
     * Test disconnect with mixed configuration clears only database credentials.
     * 
     * @test
     * Requirements: 6.2, 6.4
     */
    public function test_disconnect_with_mixed_configuration_clears_only_database_credentials(): void
    {
        // Set some credentials in config (simulating environment variables)
        Config::set('filesystems.disks.s3.key', 'AKIAIOSFODNN7EXAMPLE');
        Config::set('filesystems.disks.s3.secret', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');

        // Save other credentials to database
        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'region',
            'value' => 'us-west-2',
            'encrypted' => false,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'bucket',
            'value' => 'database-bucket',
            'encrypted' => false,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'endpoint',
            'value' => 'https://custom.endpoint.com',
            'encrypted' => false,
        ]);

        // Verify database credentials exist
        $this->assertDatabaseHas('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'region',
        ]);

        // Disconnect
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.cloud-storage.amazon-s3.disconnect'));

        $response->assertRedirect();
        $response->assertSessionHas('success', __('messages.s3_disconnected_successfully'));

        // Verify config values are still set (not modified by disconnect)
        $this->assertEquals('AKIAIOSFODNN7EXAMPLE', Config::get('filesystems.disks.s3.key'));
        $this->assertEquals('wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY', Config::get('filesystems.disks.s3.secret'));

        // Verify database credentials were removed
        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'region',
        ]);

        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'bucket',
        ]);

        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'endpoint',
        ]);
    }

    /**
     * Test disconnect displays appropriate success message.
     * 
     * @test
     * Requirements: 6.2, 6.4
     */
    public function test_disconnect_displays_appropriate_success_message(): void
    {
        // Configure S3 first
        $validConfig = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'us-east-1',
            'aws_bucket' => 'test-bucket',
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), $validConfig);

        // Disconnect
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.cloud-storage.amazon-s3.disconnect'));

        $response->assertRedirect(route('admin.cloud-storage.index'));
        $response->assertSessionHas('success', __('messages.s3_disconnected_successfully'));
        
        // Verify the message is the correct one
        $this->assertEquals(
            __('messages.s3_disconnected_successfully'),
            session('success')
        );
    }

    /**
     * Test disconnect when no configuration exists.
     * 
     * @test
     * Requirements: 6.2, 6.4
     */
    public function test_disconnect_when_no_configuration_exists(): void
    {
        // Ensure no S3 configuration exists
        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
        ]);

        // Attempt to disconnect
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.cloud-storage.amazon-s3.disconnect'));

        // Should still succeed (idempotent operation)
        $response->assertRedirect();
        $response->assertSessionHas('success', __('messages.s3_disconnected_successfully'));

        // Verify still no configuration exists
        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
        ]);
    }

    /**
     * Test disconnect with custom endpoint configuration.
     * 
     * @test
     * Requirements: 6.2, 6.4
     */
    public function test_disconnect_with_custom_endpoint_configuration(): void
    {
        // Configure S3 with custom endpoint
        $configWithEndpoint = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'auto',
            'aws_bucket' => 'cloudflare-bucket',
            'aws_endpoint' => 'https://s3.cloudflare.com',
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), $configWithEndpoint);

        // Verify endpoint was saved
        $this->assertDatabaseHas('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'endpoint',
            'value' => 'https://s3.cloudflare.com',
        ]);

        // Disconnect
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.cloud-storage.amazon-s3.disconnect'));

        $response->assertRedirect();
        $response->assertSessionHas('success', __('messages.s3_disconnected_successfully'));

        // Verify endpoint was also removed
        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'endpoint',
        ]);

        // Verify all other credentials were removed
        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
        ]);
    }

    /**
     * Test disconnect with storage class configuration.
     * 
     * @test
     * Requirements: 6.2, 6.4
     */
    public function test_disconnect_with_storage_class_configuration(): void
    {
        // Configure S3 with storage class
        $configWithStorageClass = [
            'aws_access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_region' => 'us-east-1',
            'aws_bucket' => 'test-bucket',
            'aws_storage_class' => 'INTELLIGENT_TIERING',
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.amazon-s3.update'), $configWithStorageClass);

        // Verify storage class was saved
        $this->assertDatabaseHas('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'storage_class',
            'value' => 'INTELLIGENT_TIERING',
        ]);

        // Disconnect
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.cloud-storage.amazon-s3.disconnect'));

        $response->assertRedirect();
        $response->assertSessionHas('success', __('messages.s3_disconnected_successfully'));

        // Verify storage class was also removed
        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
            'key' => 'storage_class',
        ]);

        // Verify all credentials were removed
        $this->assertDatabaseMissing('cloud_storage_settings', [
            'provider' => 'amazon-s3',
        ]);
    }
}
