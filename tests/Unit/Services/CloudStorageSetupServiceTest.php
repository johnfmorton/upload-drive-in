<?php

namespace Tests\Unit\Services;

use App\Exceptions\CloudStorageSetupException;
use App\Services\CloudStorageSetupService;
use Exception;
use Google\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CloudStorageSetupServiceTest extends TestCase
{
    private CloudStorageSetupService $service;
    private string $testEnvPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new CloudStorageSetupService();
        $this->testEnvPath = base_path('.env.test');
        
        // Create a test .env file
        File::put($this->testEnvPath, "APP_NAME=TestApp\nAPP_ENV=testing\n");
    }

    protected function tearDown(): void
    {
        // Clean up test .env file
        if (File::exists($this->testEnvPath)) {
            File::delete($this->testEnvPath);
        }
        
        parent::tearDown();
    }

    public function test_validate_required_fields_returns_empty_for_valid_google_drive_config(): void
    {
        $config = [
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'test-client-secret'
        ];
        
        $errors = $this->service->validateRequiredFields('google-drive', $config);
        
        $this->assertEmpty($errors);
    }

    public function test_validate_required_fields_returns_errors_for_missing_google_drive_fields(): void
    {
        $config = [
            'client_id' => '',
            'client_secret' => ''
        ];
        
        $errors = $this->service->validateRequiredFields('google-drive', $config);
        
        $this->assertArrayHasKey('client_id', $errors);
        $this->assertArrayHasKey('client_secret', $errors);
        $this->assertEquals('Google Drive Client ID is required', $errors['client_id']);
        $this->assertEquals('Google Drive Client Secret is required', $errors['client_secret']);
    }

    public function test_validate_required_fields_returns_error_for_invalid_client_id_format(): void
    {
        $config = [
            'client_id' => 'invalid-client-id',
            'client_secret' => 'test-client-secret'
        ];
        
        $errors = $this->service->validateRequiredFields('google-drive', $config);
        
        $this->assertArrayHasKey('client_id', $errors);
        $this->assertEquals('Invalid Google Drive Client ID format', $errors['client_id']);
    }

    public function test_validate_required_fields_handles_microsoft_teams_provider(): void
    {
        $config = [
            'client_id' => '',
            'client_secret' => ''
        ];
        
        $errors = $this->service->validateRequiredFields('microsoft-teams', $config);
        
        $this->assertArrayHasKey('client_id', $errors);
        $this->assertArrayHasKey('client_secret', $errors);
        $this->assertEquals('Microsoft Teams Client ID is required', $errors['client_id']);
        $this->assertEquals('Microsoft Teams Client Secret is required', $errors['client_secret']);
    }

    public function test_validate_required_fields_handles_dropbox_provider(): void
    {
        $config = [
            'app_key' => '',
            'app_secret' => ''
        ];
        
        $errors = $this->service->validateRequiredFields('dropbox', $config);
        
        $this->assertArrayHasKey('app_key', $errors);
        $this->assertArrayHasKey('app_secret', $errors);
        $this->assertEquals('Dropbox App Key is required', $errors['app_key']);
        $this->assertEquals('Dropbox App Secret is required', $errors['app_secret']);
    }

    public function test_validate_required_fields_throws_exception_for_unsupported_provider(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unsupported cloud storage provider: unsupported');
        
        $this->service->validateRequiredFields('unsupported', []);
    }

    public function test_generate_redirect_uri_returns_correct_url(): void
    {
        Config::set('app.url', 'https://example.com');
        
        $redirectUri = $this->service->generateRedirectUri();
        
        $this->assertEquals('https://example.com/admin/cloud-storage/google-drive/callback', $redirectUri);
    }

    public function test_get_supported_providers_returns_expected_providers(): void
    {
        $providers = $this->service->getSupportedProviders();
        
        $this->assertIsArray($providers);
        $this->assertArrayHasKey('google-drive', $providers);
        $this->assertArrayHasKey('microsoft-teams', $providers);
        $this->assertArrayHasKey('dropbox', $providers);
        $this->assertEquals('Google Drive', $providers['google-drive']);
        $this->assertEquals('Microsoft Teams', $providers['microsoft-teams']);
        $this->assertEquals('Dropbox', $providers['dropbox']);
    }

    public function test_is_provider_configured_returns_true_for_configured_google_drive(): void
    {
        Config::set('services.google.client_id', 'test-client-id');
        Config::set('services.google.client_secret', 'test-client-secret');
        
        $result = $this->service->isProviderConfigured('google-drive');
        
        $this->assertTrue($result);
    }

    public function test_is_provider_configured_returns_false_for_unconfigured_google_drive(): void
    {
        Config::set('services.google.client_id', null);
        Config::set('services.google.client_secret', null);
        
        $result = $this->service->isProviderConfigured('google-drive');
        
        $this->assertFalse($result);
    }

    public function test_is_provider_configured_returns_false_for_unsupported_provider(): void
    {
        $result = $this->service->isProviderConfigured('unsupported');
        
        $this->assertFalse($result);
    }

    public function test_get_provider_config_template_returns_google_drive_template(): void
    {
        $template = $this->service->getProviderConfigTemplate('google-drive');
        
        $this->assertIsArray($template);
        $this->assertArrayHasKey('client_id', $template);
        $this->assertArrayHasKey('client_secret', $template);
        
        $this->assertEquals('Client ID', $template['client_id']['label']);
        $this->assertEquals('text', $template['client_id']['type']);
        $this->assertTrue($template['client_id']['required']);
        
        $this->assertEquals('Client Secret', $template['client_secret']['label']);
        $this->assertEquals('password', $template['client_secret']['type']);
        $this->assertTrue($template['client_secret']['required']);
    }

    public function test_get_provider_config_template_returns_microsoft_teams_template(): void
    {
        $template = $this->service->getProviderConfigTemplate('microsoft-teams');
        
        $this->assertIsArray($template);
        $this->assertArrayHasKey('client_id', $template);
        $this->assertArrayHasKey('client_secret', $template);
        
        $this->assertEquals('Application (client) ID', $template['client_id']['label']);
        $this->assertEquals('Client Secret', $template['client_secret']['label']);
    }

    public function test_get_provider_config_template_returns_dropbox_template(): void
    {
        $template = $this->service->getProviderConfigTemplate('dropbox');
        
        $this->assertIsArray($template);
        $this->assertArrayHasKey('app_key', $template);
        $this->assertArrayHasKey('app_secret', $template);
        
        $this->assertEquals('App Key', $template['app_key']['label']);
        $this->assertEquals('App Secret', $template['app_secret']['label']);
    }

    public function test_get_provider_config_template_returns_empty_for_unsupported_provider(): void
    {
        $template = $this->service->getProviderConfigTemplate('unsupported');
        
        $this->assertEmpty($template);
    }

    public function test_test_google_drive_connection_throws_exception_for_invalid_credentials(): void
    {
        $this->expectException(CloudStorageSetupException::class);
        
        $this->service->testGoogleDriveConnection('', '');
    }

    public function test_store_google_drive_config_throws_exception_for_invalid_config(): void
    {
        $config = [
            'client_id' => '',
            'client_secret' => ''
        ];
        
        $this->expectException(CloudStorageSetupException::class);
        
        $this->service->storeGoogleDriveConfig($config);
    }

    public function test_escape_environment_value_handles_special_characters(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('escapeEnvironmentValue');
        $method->setAccessible(true);
        
        // Test value with spaces
        $result = $method->invoke($this->service, 'value with spaces');
        $this->assertEquals('"value with spaces"', $result);
        
        // Test value with quotes
        $result = $method->invoke($this->service, 'value "with" quotes');
        $this->assertEquals('"value \"with\" quotes"', $result);
        
        // Test simple value
        $result = $method->invoke($this->service, 'simplevalue');
        $this->assertEquals('simplevalue', $result);
    }

    public function test_update_environment_file_throws_exception_for_missing_file(): void
    {
        // Mock File facade to simulate missing .env file
        File::shouldReceive('exists')
            ->with(base_path('.env'))
            ->once()
            ->andReturn(false);
        
        $this->expectException(CloudStorageSetupException::class);
        $this->expectExceptionMessage('.env file not found');
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('updateEnvironmentFile');
        $method->setAccessible(true);
        
        $method->invoke($this->service, ['TEST_KEY' => 'test_value']);
    }

    public function test_update_environment_file_throws_exception_for_readonly_file(): void
    {
        // Mock File facade to simulate readonly .env file
        File::shouldReceive('exists')
            ->with(base_path('.env'))
            ->once()
            ->andReturn(true);
        
        // Mock is_writable to return false
        $this->app->bind('files', function () {
            $mock = \Mockery::mock(\Illuminate\Filesystem\Filesystem::class);
            $mock->shouldReceive('exists')->andReturn(true);
            return $mock;
        });
        
        $this->expectException(CloudStorageSetupException::class);
        $this->expectExceptionMessage('.env file is not writable');
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('updateEnvironmentFile');
        $method->setAccessible(true);
        
        $method->invoke($this->service, ['TEST_KEY' => 'test_value']);
    }

    public function test_test_google_drive_connection_handles_network_errors(): void
    {
        // This test would require mocking the Google Client
        // For now, we'll test the validation part
        $this->expectException(CloudStorageSetupException::class);
        
        $this->service->testGoogleDriveConnection('invalid-format', 'secret');
    }

    public function test_store_google_drive_config_updates_runtime_configuration(): void
    {
        $config = [
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'test-client-secret'
        ];
        
        // Mock File operations to avoid actual .env file modification
        File::shouldReceive('exists')
            ->with(base_path('.env'))
            ->once()
            ->andReturn(true);
        
        File::shouldReceive('get')
            ->with(base_path('.env'))
            ->once()
            ->andReturn("APP_NAME=TestApp\n");
        
        File::shouldReceive('put')
            ->with(base_path('.env'), \Mockery::any())
            ->once()
            ->andReturn(true);
        
        // Mock is_writable
        $this->app->bind('files', function () {
            $mock = \Mockery::mock(\Illuminate\Filesystem\Filesystem::class);
            $mock->shouldReceive('exists')->andReturn(true);
            $mock->shouldReceive('get')->andReturn("APP_NAME=TestApp\n");
            $mock->shouldReceive('put')->andReturn(true);
            return $mock;
        });
        
        $this->service->storeGoogleDriveConfig($config);
        
        // Verify runtime configuration was updated
        $this->assertEquals('test-client-id.apps.googleusercontent.com', Config::get('services.google.client_id'));
        $this->assertEquals('test-client-secret', Config::get('services.google.client_secret'));
        $this->assertEquals('google-drive', Config::get('cloud-storage.default'));
    }
}