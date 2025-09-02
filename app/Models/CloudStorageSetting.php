<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class CloudStorageSetting extends Model
{
    protected $fillable = [
        'provider',
        'key',
        'value',
        'encrypted',
    ];

    protected $casts = [
        'encrypted' => 'boolean',
    ];

    /**
     * Get the decrypted value if encrypted, otherwise return raw value.
     */
    public function getDecryptedValueAttribute(): ?string
    {
        if ($this->encrypted && $this->value) {
            try {
                return Crypt::decryptString($this->value);
            } catch (\Exception $e) {
                return null;
            }
        }
        
        return $this->value;
    }

    /**
     * Set the value, encrypting it if needed.
     */
    public function setEncryptedValue(string $value, bool $encrypt = false): void
    {
        $this->encrypted = $encrypt;
        $this->value = $encrypt ? Crypt::encryptString($value) : $value;
    }

    /**
     * Get a setting value for a provider and key.
     */
    public static function getValue(string $provider, string $key): ?string
    {
        $setting = static::where('provider', $provider)
            ->where('key', $key)
            ->first();

        return $setting?->decrypted_value;
    }

    /**
     * Set a setting value for a provider and key.
     */
    public static function setValue(string $provider, string $key, ?string $value, bool $encrypt = false): void
    {
        $setting = static::updateOrCreate(
            ['provider' => $provider, 'key' => $key],
            []
        );

        if ($value === null) {
            $setting->delete();
            return;
        }

        $setting->setEncryptedValue($value, $encrypt);
        $setting->save();
    }

    /**
     * Get the environment key for a provider and setting key.
     */
    private static function getEnvironmentKey(string $provider, string $key): string
    {
        $providerKey = str_replace('-', '_', strtoupper($provider));
        $settingKey = strtoupper($key);
        
        return "{$providerKey}_{$settingKey}";
    }

    /**
     * Check if a setting is defined in environment variables.
     */
    public static function isDefinedInEnvironment(string $provider, string $key): bool
    {
        $envKey = static::getEnvironmentKey($provider, $key);
        $envValue = env($envKey);
        return $envValue !== null && $envValue !== '';
    }

    /**
     * Get the effective value (environment first, then database).
     */
    public static function getEffectiveValue(string $provider, string $key): ?string
    {
        $envKey = static::getEnvironmentKey($provider, $key);
        $envValue = env($envKey);
        
        if ($envValue !== null && $envValue !== '') {
            return $envValue;
        }

        return static::getValue($provider, $key);
    }

    /**
     * Get all effective configuration for a provider.
     */
    public static function getProviderConfig(string $provider): array
    {
        $config = [];
        
        // Define the keys we need for each provider
        $providerKeys = [
            'google-drive' => ['client_id', 'client_secret'],
            'microsoft-teams' => ['client_id', 'client_secret', 'root_folder_id'],
            'dropbox' => ['client_id', 'client_secret', 'root_folder'],
        ];

        if (!isset($providerKeys[$provider])) {
            return $config;
        }

        foreach ($providerKeys[$provider] as $key) {
            $config[$key] = static::getEffectiveValue($provider, $key);
        }

        return $config;
    }

    /**
     * Get provider schema information
     */
    public static function getProviderSchema(string $provider): array
    {
        $schemas = [
            'google-drive' => [
                'required' => ['client_id', 'client_secret'],
                'optional' => ['redirect_uri', 'root_folder_id'],
                'encrypted' => ['client_secret'],
                'auth_type' => 'oauth',
                'storage_model' => 'hierarchical',
            ],
            'amazon-s3' => [
                'required' => ['access_key_id', 'secret_access_key', 'region', 'bucket'],
                'optional' => ['endpoint', 'storage_class'],
                'encrypted' => ['secret_access_key'],
                'auth_type' => 'api_key',
                'storage_model' => 'flat',
            ],
            'azure-blob' => [
                'required' => ['connection_string', 'container'],
                'optional' => ['access_tier'],
                'encrypted' => ['connection_string'],
                'auth_type' => 'connection_string',
                'storage_model' => 'flat',
            ],
            'microsoft-teams' => [
                'required' => ['client_id', 'client_secret'],
                'optional' => ['redirect_uri', 'root_folder_id'],
                'encrypted' => ['client_secret'],
                'auth_type' => 'oauth',
                'storage_model' => 'hierarchical',
            ],
            'dropbox' => [
                'required' => ['app_key', 'app_secret'],
                'optional' => ['redirect_uri', 'root_folder'],
                'encrypted' => ['app_secret'],
                'auth_type' => 'oauth',
                'storage_model' => 'hierarchical',
            ],
        ];

        return $schemas[$provider] ?? [];
    }

    /**
     * Validate provider configuration against schema
     */
    public static function validateProviderConfig(string $provider, array $config): array
    {
        $errors = [];
        $schema = static::getProviderSchema($provider);

        if (empty($schema)) {
            $errors[] = "Unknown provider: {$provider}";
            return $errors;
        }

        // Check required keys
        foreach ($schema['required'] as $requiredKey) {
            if (!isset($config[$requiredKey]) || empty($config[$requiredKey])) {
                $errors[] = "Missing required configuration key: {$requiredKey}";
            }
        }

        return $errors;
    }

    /**
     * Get required keys for a provider
     */
    public static function getRequiredKeys(string $provider): array
    {
        $schema = static::getProviderSchema($provider);
        return $schema['required'] ?? [];
    }

    /**
     * Get optional keys for a provider
     */
    public static function getOptionalKeys(string $provider): array
    {
        $schema = static::getProviderSchema($provider);
        return $schema['optional'] ?? [];
    }

    /**
     * Get encrypted keys for a provider
     */
    public static function getEncryptedKeys(string $provider): array
    {
        $schema = static::getProviderSchema($provider);
        return $schema['encrypted'] ?? [];
    }

    /**
     * Migrate configuration from environment to database
     */
    public static function migrateFromEnvironment(string $provider): array
    {
        $migrated = [];
        $schema = static::getProviderSchema($provider);
        
        if (empty($schema)) {
            return $migrated;
        }

        $allKeys = array_merge($schema['required'], $schema['optional']);

        foreach ($allKeys as $key) {
            $envKey = static::getEnvironmentKey($provider, $key);
            $envValue = env($envKey);

            if ($envValue !== null && $envValue !== '') {
                // Only migrate if not already in database
                if (static::getValue($provider, $key) === null) {
                    $shouldEncrypt = in_array($key, $schema['encrypted']);
                    static::setValue($provider, $key, $envValue, $shouldEncrypt);
                    $migrated[] = $key;
                }
            }
        }

        return $migrated;
    }
}
