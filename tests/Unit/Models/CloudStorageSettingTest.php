<?php

namespace Tests\Unit\Models;

use App\Models\CloudStorageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class CloudStorageSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_provider_schema_returns_correct_schema_for_google_drive(): void
    {
        $schema = CloudStorageSetting::getProviderSchema('google-drive');

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('required', $schema);
        $this->assertArrayHasKey('optional', $schema);
        $this->assertArrayHasKey('encrypted', $schema);
        $this->assertArrayHasKey('auth_type', $schema);
        $this->assertArrayHasKey('storage_model', $schema);

        $this->assertEquals(['client_id', 'client_secret'], $schema['required']);
        $this->assertEquals(['redirect_uri', 'root_folder_id'], $schema['optional']);
        $this->assertEquals(['client_secret'], $schema['encrypted']);
        $this->assertEquals('oauth', $schema['auth_type']);
        $this->assertEquals('hierarchical', $schema['storage_model']);
    }

    public function test_get_provider_schema_returns_correct_schema_for_amazon_s3(): void
    {
        $schema = CloudStorageSetting::getProviderSchema('amazon-s3');

        $this->assertIsArray($schema);
        $this->assertEquals(['access_key_id', 'secret_access_key', 'region', 'bucket'], $schema['required']);
        $this->assertEquals(['endpoint', 'storage_class'], $schema['optional']);
        $this->assertEquals(['secret_access_key'], $schema['encrypted']);
        $this->assertEquals('api_key', $schema['auth_type']);
        $this->assertEquals('flat', $schema['storage_model']);
    }

    public function test_get_provider_schema_returns_empty_for_unknown_provider(): void
    {
        $schema = CloudStorageSetting::getProviderSchema('unknown-provider');

        $this->assertEmpty($schema);
    }

    public function test_validate_provider_config_passes_with_valid_config(): void
    {
        $config = [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
        ];

        $errors = CloudStorageSetting::validateProviderConfig('google-drive', $config);

        $this->assertEmpty($errors);
    }

    public function test_validate_provider_config_fails_with_missing_required_keys(): void
    {
        $config = [
            'client_id' => 'test-client-id',
            // Missing client_secret
        ];

        $errors = CloudStorageSetting::validateProviderConfig('google-drive', $config);

        $this->assertNotEmpty($errors);
        $this->assertContains('Missing required configuration key: client_secret', $errors);
    }

    public function test_validate_provider_config_fails_with_empty_required_values(): void
    {
        $config = [
            'client_id' => '',
            'client_secret' => 'test-client-secret',
        ];

        $errors = CloudStorageSetting::validateProviderConfig('google-drive', $config);

        $this->assertNotEmpty($errors);
        $this->assertContains('Missing required configuration key: client_id', $errors);
    }

    public function test_validate_provider_config_fails_with_unknown_provider(): void
    {
        $errors = CloudStorageSetting::validateProviderConfig('unknown-provider', []);

        $this->assertNotEmpty($errors);
        $this->assertContains('Unknown provider: unknown-provider', $errors);
    }

    public function test_get_required_keys_returns_correct_keys(): void
    {
        $requiredKeys = CloudStorageSetting::getRequiredKeys('google-drive');

        $this->assertEquals(['client_id', 'client_secret'], $requiredKeys);
    }

    public function test_get_optional_keys_returns_correct_keys(): void
    {
        $optionalKeys = CloudStorageSetting::getOptionalKeys('google-drive');

        $this->assertEquals(['redirect_uri', 'root_folder_id'], $optionalKeys);
    }

    public function test_get_encrypted_keys_returns_correct_keys(): void
    {
        $encryptedKeys = CloudStorageSetting::getEncryptedKeys('google-drive');

        $this->assertEquals(['client_secret'], $encryptedKeys);
    }

    public function test_get_required_keys_returns_empty_for_unknown_provider(): void
    {
        $requiredKeys = CloudStorageSetting::getRequiredKeys('unknown-provider');

        $this->assertEmpty($requiredKeys);
    }

    public function test_set_encrypted_value_encrypts_when_requested(): void
    {
        $setting = new CloudStorageSetting();
        $setting->setEncryptedValue('sensitive-data', true);

        $this->assertTrue($setting->encrypted);
        $this->assertNotEquals('sensitive-data', $setting->value);
    }

    public function test_set_encrypted_value_does_not_encrypt_when_not_requested(): void
    {
        $setting = new CloudStorageSetting();
        $setting->setEncryptedValue('normal-data', false);

        $this->assertFalse($setting->encrypted);
        $this->assertEquals('normal-data', $setting->value);
    }

    public function test_get_decrypted_value_attribute_decrypts_encrypted_values(): void
    {
        $setting = new CloudStorageSetting();
        $setting->encrypted = true;
        $setting->value = Crypt::encryptString('encrypted-data');

        $this->assertEquals('encrypted-data', $setting->decrypted_value);
    }

    public function test_get_decrypted_value_attribute_returns_raw_value_for_unencrypted(): void
    {
        $setting = new CloudStorageSetting();
        $setting->encrypted = false;
        $setting->value = 'plain-data';

        $this->assertEquals('plain-data', $setting->decrypted_value);
    }

    public function test_get_decrypted_value_attribute_handles_decryption_failure(): void
    {
        $setting = new CloudStorageSetting();
        $setting->encrypted = true;
        $setting->value = 'invalid-encrypted-data';

        $this->assertNull($setting->decrypted_value);
    }

    public function test_set_value_creates_new_setting(): void
    {
        CloudStorageSetting::setValue('test-provider', 'test-key', 'test-value');

        $setting = CloudStorageSetting::where('provider', 'test-provider')
            ->where('key', 'test-key')
            ->first();

        $this->assertNotNull($setting);
        $this->assertEquals('test-value', $setting->value);
        $this->assertFalse($setting->encrypted);
    }

    public function test_set_value_updates_existing_setting(): void
    {
        CloudStorageSetting::create([
            'provider' => 'test-provider',
            'key' => 'test-key',
            'value' => 'old-value',
            'encrypted' => false,
        ]);

        CloudStorageSetting::setValue('test-provider', 'test-key', 'new-value');

        $setting = CloudStorageSetting::where('provider', 'test-provider')
            ->where('key', 'test-key')
            ->first();

        $this->assertEquals('new-value', $setting->value);
    }

    public function test_set_value_deletes_setting_when_value_is_null(): void
    {
        CloudStorageSetting::create([
            'provider' => 'test-provider',
            'key' => 'test-key',
            'value' => 'test-value',
            'encrypted' => false,
        ]);

        CloudStorageSetting::setValue('test-provider', 'test-key', null);

        $setting = CloudStorageSetting::where('provider', 'test-provider')
            ->where('key', 'test-key')
            ->first();

        $this->assertNull($setting);
    }

    public function test_get_value_returns_decrypted_value(): void
    {
        CloudStorageSetting::setValue('test-provider', 'test-key', 'test-value', true);

        $value = CloudStorageSetting::getValue('test-provider', 'test-key');

        $this->assertEquals('test-value', $value);
    }

    public function test_get_value_returns_null_for_nonexistent_setting(): void
    {
        $value = CloudStorageSetting::getValue('nonexistent-provider', 'nonexistent-key');

        $this->assertNull($value);
    }

    public function test_migrate_from_environment_returns_empty_array_for_unknown_provider(): void
    {
        $migrated = CloudStorageSetting::migrateFromEnvironment('unknown-provider');

        $this->assertIsArray($migrated);
        $this->assertEmpty($migrated);
    }

    public function test_migrate_from_environment_does_not_overwrite_existing_database_values(): void
    {
        // Set existing database value
        CloudStorageSetting::setValue('google-drive', 'client_id', 'existing-value');

        // Attempt migration (would use env values if they existed)
        $migrated = CloudStorageSetting::migrateFromEnvironment('google-drive');

        // Should not have migrated client_id since database value exists
        // But may have migrated other keys that don't exist in database
        $this->assertNotContains('client_id', $migrated);
        $this->assertEquals('existing-value', CloudStorageSetting::getValue('google-drive', 'client_id'));
    }
}