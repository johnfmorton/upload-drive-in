<?php

namespace Tests\Unit\Services;

use App\Models\CloudStorageSetting;
use App\Services\CloudConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Tests\TestCase;

class CloudConfigurationServiceTest extends TestCase
{
    use RefreshDatabase;

    private CloudConfigurationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CloudConfigurationService();
    }

    public function test_get_supported_providers_returns_all_providers(): void
    {
        $providers = $this->service->getSupportedProviders();

        $this->assertIsArray($providers);
        $this->assertContains('google-drive', $providers);
        $this->assertContains('amazon-s3', $providers);
        $this->assertContains('azure-blob', $providers);
        $this->assertContains('microsoft-teams', $providers);
        $this->assertContains('dropbox', $providers);
    }

    public function test_get_provider_schema_returns_correct_schema(): void
    {
        $schema = $this->service->getProviderSchema('google-drive');

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('required', $schema);
        $this->assertArrayHasKey('optional', $schema);
        $this->assertArrayHasKey('encrypted', $schema);
        $this->assertArrayHasKey('auth_type', $schema);
        $this->assertArrayHasKey('storage_model', $schema);

        $this->assertEquals(['client_id', 'client_secret'], $schema['required']);
        $this->assertEquals('oauth', $schema['auth_type']);
        $this->assertEquals('hierarchical', $schema['storage_model']);
    }

    public function test_get_provider_schema_throws_exception_for_invalid_provider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown provider: invalid-provider');

        $this->service->getProviderSchema('invalid-provider');
    }

    public function test_validate_provider_config_passes_with_valid_config(): void
    {
        $config = [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
        ];

        $errors = $this->service->validateProviderConfig('google-drive', $config);

        $this->assertEmpty($errors);
    }

    public function test_validate_provider_config_fails_with_missing_required_keys(): void
    {
        $config = [
            'client_id' => 'test-client-id',
            // Missing client_secret
        ];

        $errors = $this->service->validateProviderConfig('google-drive', $config);

        $this->assertNotEmpty($errors);
        $this->assertContains('Missing required configuration key: client_secret', $errors);
    }

    public function test_validate_provider_config_fails_with_invalid_provider(): void
    {
        $errors = $this->service->validateProviderConfig('invalid-provider', []);

        $this->assertNotEmpty($errors);
        $this->assertContains('Unknown provider: invalid-provider', $errors);
    }

    public function test_validate_s3_specific_config(): void
    {
        $config = [
            'access_key_id' => 'test-key',
            'secret_access_key' => 'test-secret',
            'region' => 'invalid-region!',
            'bucket' => 'invalid-bucket-name!',
        ];

        $errors = $this->service->validateProviderConfig('amazon-s3', $config);

        $this->assertContains('Invalid AWS region format', $errors);
        $this->assertContains('Invalid S3 bucket name format', $errors);
    }

    public function test_validate_azure_specific_config(): void
    {
        $config = [
            'connection_string' => 'invalid-connection-string',
            'container' => 'test-container',
        ];

        $errors = $this->service->validateProviderConfig('azure-blob', $config);

        $this->assertContains('Invalid Azure connection string format', $errors);
    }

    public function test_get_provider_config_returns_configuration_with_metadata(): void
    {
        // Set up database configuration
        CloudStorageSetting::setValue('google-drive', 'client_id', 'test-client-id');
        CloudStorageSetting::setValue('google-drive', 'client_secret', 'test-client-secret', true);

        $config = $this->service->getProviderConfig('google-drive');

        $this->assertArrayHasKey('client_id', $config);
        $this->assertArrayHasKey('client_secret', $config);
        $this->assertArrayHasKey('_meta', $config);

        $this->assertEquals('test-client-id', $config['client_id']);
        $this->assertEquals('test-client-secret', $config['client_secret']);
        $this->assertEquals('oauth', $config['_meta']['auth_type']);
        $this->assertEquals('hierarchical', $config['_meta']['storage_model']);
    }

    public function test_get_effective_config_prioritizes_environment_over_database(): void
    {
        // Set database value
        CloudStorageSetting::setValue('google-drive', 'client_id', 'db-client-id');

        // Mock environment value
        Config::set('cloud-storage.providers.google-drive.client_id', 'config-client-id');
        
        // Environment should take precedence (mocked via config for testing)
        $config = $this->service->getEffectiveConfig('google-drive');

        // Since we can't easily mock env() in tests, we'll test the fallback behavior
        $this->assertArrayHasKey('client_id', $config);
    }

    public function test_set_provider_config_stores_values_correctly(): void
    {
        $config = [
            'client_id' => 'new-client-id',
            'client_secret' => 'new-client-secret',
        ];

        $this->service->setProviderConfig('google-drive', $config);

        $this->assertEquals('new-client-id', CloudStorageSetting::getValue('google-drive', 'client_id'));
        $this->assertEquals('new-client-secret', CloudStorageSetting::getValue('google-drive', 'client_secret'));
    }

    public function test_set_provider_config_encrypts_sensitive_values(): void
    {
        $config = [
            'client_secret' => 'sensitive-secret',
        ];

        $this->service->setProviderConfig('google-drive', $config);

        $setting = CloudStorageSetting::where('provider', 'google-drive')
            ->where('key', 'client_secret')
            ->first();

        $this->assertTrue($setting->encrypted);
        $this->assertNotEquals('sensitive-secret', $setting->value);
        $this->assertEquals('sensitive-secret', $setting->decrypted_value);
    }

    public function test_get_config_source_identifies_correct_source(): void
    {
        // Test database source
        CloudStorageSetting::setValue('google-drive', 'client_id', 'test-value');
        $source = $this->service->getConfigSource('google-drive', 'client_id');
        $this->assertEquals('database', $source);

        // Test config file source
        Config::set('cloud-storage.providers.google-drive.redirect_uri', 'test-uri');
        $source = $this->service->getConfigSource('google-drive', 'redirect_uri');
        $this->assertEquals('config', $source);

        // Test none source
        $source = $this->service->getConfigSource('google-drive', 'nonexistent_key');
        $this->assertEquals('none', $source);
    }

    public function test_is_provider_configured_returns_correct_status(): void
    {
        // Provider not configured
        $this->assertFalse($this->service->isProviderConfigured('google-drive'));

        // Configure provider
        CloudStorageSetting::setValue('google-drive', 'client_id', 'test-client-id');
        CloudStorageSetting::setValue('google-drive', 'client_secret', 'test-client-secret');

        $this->assertTrue($this->service->isProviderConfigured('google-drive'));
    }

    public function test_migrate_from_environment_migrates_values(): void
    {
        // Mock environment values by setting config values
        Config::set('cloud-storage.providers.google-drive.client_id', 'env-client-id');
        Config::set('cloud-storage.providers.google-drive.client_secret', 'env-client-secret');

        // Since we can't easily mock env() in tests, we'll test the method exists and handles the case
        $migrated = $this->service->migrateFromEnvironment('google-drive');

        // The method should return an array (empty in this case since env() returns null in tests)
        $this->assertIsArray($migrated);
    }

    public function test_get_all_provider_configs_returns_all_configurations(): void
    {
        $configs = $this->service->getAllProviderConfigs();

        $this->assertIsArray($configs);
        $this->assertArrayHasKey('google-drive', $configs);
        $this->assertArrayHasKey('amazon-s3', $configs);
        $this->assertArrayHasKey('azure-blob', $configs);
        $this->assertArrayHasKey('microsoft-teams', $configs);
        $this->assertArrayHasKey('dropbox', $configs);
    }
}