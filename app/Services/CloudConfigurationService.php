<?php

namespace App\Services;

use App\Models\CloudStorageSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CloudConfigurationService
{
    /**
     * Provider configuration schemas defining required and optional keys
     */
    private const PROVIDER_SCHEMAS = [
        'google-drive' => [
            'required' => ['client_id', 'client_secret'],
            'optional' => ['redirect_uri', 'root_folder_id'],
            'encrypted' => ['client_secret'],
            'auth_type' => 'oauth',
            'storage_model' => 'hierarchical',
        ],
        'amazon-s3' => [
            'required' => ['access_key_id', 'secret_access_key', 'region', 'bucket'],
            'optional' => ['endpoint', 'storage_class', 'folder_path'],
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

    /**
     * Get provider configuration from all sources (environment, database, config)
     */
    public function getProviderConfig(string $providerName): array
    {
        if (!$this->isValidProvider($providerName)) {
            throw new InvalidArgumentException("Unknown provider: {$providerName}");
        }

        $config = [];
        $schema = self::PROVIDER_SCHEMAS[$providerName];
        $allKeys = array_merge($schema['required'], $schema['optional']);

        // Get configuration from all sources
        foreach ($allKeys as $key) {
            $value = $this->getEffectiveValue($providerName, $key);
            if ($value !== null) {
                $config[$key] = $value;
            }
        }

        // Add metadata
        $config['_meta'] = [
            'auth_type' => $schema['auth_type'],
            'storage_model' => $schema['storage_model'],
        ];

        return $config;
    }

    /**
     * Validate provider configuration against schema
     */
    public function validateProviderConfig(string $providerName, array $config): array
    {
        $errors = [];
        
        if (!$this->isValidProvider($providerName)) {
            $errors[] = "Unknown provider: {$providerName}";
            return $errors;
        }

        $schema = self::PROVIDER_SCHEMAS[$providerName];

        // Check required keys
        foreach ($schema['required'] as $requiredKey) {
            if (!isset($config[$requiredKey]) || empty($config[$requiredKey])) {
                $errors[] = "Missing required configuration key: {$requiredKey}";
            }
        }

        // Validate specific configurations
        $errors = array_merge($errors, $this->validateProviderSpecificConfig($providerName, $config));

        return $errors;
    }

    /**
     * Get effective configuration value (environment > database > config file)
     */
    public function getEffectiveConfig(string $providerName): array
    {
        $config = $this->getProviderConfig($providerName);
        
        // Add config file values as fallback
        $configFileValues = Config::get("cloud-storage.providers.{$providerName}.config", []);
        foreach ($configFileValues as $key => $value) {
            if (!isset($config[$key]) && $value !== null) {
                $config[$key] = $value;
            }
        }

        return $config;
    }

    /**
     * Set provider configuration value
     */
    public function setProviderConfig(string $providerName, array $config): void
    {
        if (!$this->isValidProvider($providerName)) {
            throw new InvalidArgumentException("Unknown provider: {$providerName}");
        }

        $schema = self::PROVIDER_SCHEMAS[$providerName];
        
        foreach ($config as $key => $value) {
            $shouldEncrypt = in_array($key, $schema['encrypted']);
            CloudStorageSetting::setValue($providerName, $key, $value, $shouldEncrypt);
        }

        Log::info("Provider configuration updated", [
            'provider' => $providerName,
            'keys' => array_keys($config),
        ]);
    }

    /**
     * Get configuration source for a specific key
     */
    public function getConfigSource(string $providerName, string $key): string
    {
        // Check environment first
        $envKey = $this->getEnvironmentKey($providerName, $key);
        if (env($envKey) !== null) {
            return 'environment';
        }

        // Check database
        if (CloudStorageSetting::getValue($providerName, $key) !== null) {
            return 'database';
        }

        // Check config file
        $configValue = Config::get("cloud-storage.providers.{$providerName}.config.{$key}");
        if ($configValue !== null) {
            return 'config';
        }

        return 'none';
    }

    /**
     * Get all provider configurations
     */
    public function getAllProviderConfigs(): array
    {
        $configs = [];
        
        foreach (array_keys(self::PROVIDER_SCHEMAS) as $provider) {
            $configs[$provider] = $this->getEffectiveConfig($provider);
        }

        return $configs;
    }

    /**
     * Check if provider is configured (has all required keys)
     */
    public function isProviderConfigured(string $providerName): bool
    {
        if (!$this->isValidProvider($providerName)) {
            return false;
        }

        $config = $this->getEffectiveConfig($providerName);
        $errors = $this->validateProviderConfig($providerName, $config);

        return empty($errors);
    }

    /**
     * Get provider schema information
     */
    public function getProviderSchema(string $providerName): array
    {
        if (!$this->isValidProvider($providerName)) {
            throw new InvalidArgumentException("Unknown provider: {$providerName}");
        }

        return self::PROVIDER_SCHEMAS[$providerName];
    }

    /**
     * Get all supported providers
     */
    public function getSupportedProviders(): array
    {
        return array_keys(self::PROVIDER_SCHEMAS);
    }

    /**
     * Migrate configuration from environment to database
     */
    public function migrateFromEnvironment(string $providerName): array
    {
        if (!$this->isValidProvider($providerName)) {
            throw new InvalidArgumentException("Unknown provider: {$providerName}");
        }

        $migrated = [];
        $schema = self::PROVIDER_SCHEMAS[$providerName];
        $allKeys = array_merge($schema['required'], $schema['optional']);

        foreach ($allKeys as $key) {
            $envKey = $this->getEnvironmentKey($providerName, $key);
            $envValue = env($envKey);

            if ($envValue !== null && $envValue !== '') {
                // Only migrate if not already in database
                if (CloudStorageSetting::getValue($providerName, $key) === null) {
                    $shouldEncrypt = in_array($key, $schema['encrypted']);
                    CloudStorageSetting::setValue($providerName, $key, $envValue, $shouldEncrypt);
                    $migrated[] = $key;
                }
            }
        }

        if (!empty($migrated)) {
            Log::info("Migrated configuration from environment", [
                'provider' => $providerName,
                'keys' => $migrated,
            ]);
        }

        return $migrated;
    }

    /**
     * Get effective value from all sources (environment > database > config)
     */
    private function getEffectiveValue(string $providerName, string $key): ?string
    {
        // Environment takes precedence
        $envKey = $this->getEnvironmentKey($providerName, $key);
        $envValue = env($envKey);
        if ($envValue !== null && $envValue !== '') {
            return $envValue;
        }

        // Database second
        $dbValue = CloudStorageSetting::getValue($providerName, $key);
        if ($dbValue !== null) {
            return $dbValue;
        }

        // Config file last
        return Config::get("cloud-storage.providers.{$providerName}.config.{$key}");
    }

    /**
     * Get environment variable key for provider and setting
     */
    private function getEnvironmentKey(string $providerName, string $key): string
    {
        $providerKey = str_replace('-', '_', strtoupper($providerName));
        $settingKey = strtoupper($key);
        
        return "{$providerKey}_{$settingKey}";
    }

    /**
     * Check if provider is valid
     */
    private function isValidProvider(string $providerName): bool
    {
        return array_key_exists($providerName, self::PROVIDER_SCHEMAS);
    }

    /**
     * Validate provider-specific configuration rules
     */
    private function validateProviderSpecificConfig(string $providerName, array $config): array
    {
        $errors = [];

        switch ($providerName) {
            case 'amazon-s3':
                if (isset($config['region']) && !preg_match('/^[a-z0-9-]+$/', $config['region'])) {
                    $errors[] = 'Invalid AWS region format';
                }
                if (isset($config['bucket']) && !preg_match('/^[a-z0-9.-]+$/', $config['bucket'])) {
                    $errors[] = 'Invalid S3 bucket name format';
                }
                break;

            case 'azure-blob':
                if (isset($config['connection_string']) && !str_contains($config['connection_string'], 'AccountName=')) {
                    $errors[] = 'Invalid Azure connection string format';
                }
                break;

            case 'google-drive':
            case 'microsoft-teams':
            case 'dropbox':
                // OAuth providers - validate client credentials format
                if (isset($config['client_id']) && empty(trim($config['client_id']))) {
                    $errors[] = 'Client ID cannot be empty';
                }
                if (isset($config['client_secret']) && empty(trim($config['client_secret']))) {
                    $errors[] = 'Client secret cannot be empty';
                }
                break;
        }

        return $errors;
    }
}