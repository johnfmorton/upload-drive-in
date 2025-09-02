<?php

namespace App\Services;

use App\Contracts\CloudStorageProviderInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CloudStorageConfigurationValidationService
{
    public function __construct(
        private readonly CloudConfigurationService $configService,
        private readonly CloudStorageFactory $factory
    ) {}

    /**
     * Validate all provider configurations at startup
     */
    public function validateAllProviderConfigurations(): array
    {
        $results = [
            'valid' => [],
            'invalid' => [],
            'warnings' => [],
            'summary' => [
                'total_providers' => 0,
                'valid_count' => 0,
                'invalid_count' => 0,
                'warning_count' => 0,
            ]
        ];

        $supportedProviders = $this->configService->getSupportedProviders();
        $results['summary']['total_providers'] = count($supportedProviders);

        foreach ($supportedProviders as $providerName) {
            try {
                $validationResult = $this->validateProviderConfiguration($providerName);
                
                if ($validationResult['is_valid']) {
                    $results['valid'][$providerName] = $validationResult;
                    $results['summary']['valid_count']++;
                } else {
                    $results['invalid'][$providerName] = $validationResult;
                    $results['summary']['invalid_count']++;
                }

                if (!empty($validationResult['warnings'])) {
                    $results['warnings'][$providerName] = $validationResult['warnings'];
                    $results['summary']['warning_count']++;
                }

            } catch (\Exception $e) {
                $results['invalid'][$providerName] = [
                    'is_valid' => false,
                    'errors' => ["Configuration validation failed: {$e->getMessage()}"],
                    'warnings' => [],
                    'provider_name' => $providerName,
                    'exception' => $e->getMessage(),
                ];
                $results['summary']['invalid_count']++;

                Log::error('Provider configuration validation failed', [
                    'provider' => $providerName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Provider configuration validation completed', [
            'total_providers' => $results['summary']['total_providers'],
            'valid_count' => $results['summary']['valid_count'],
            'invalid_count' => $results['summary']['invalid_count'],
            'warning_count' => $results['summary']['warning_count'],
        ]);

        return $results;
    }

    /**
     * Validate configuration for a specific provider
     */
    public function validateProviderConfiguration(string $providerName): array
    {
        $result = [
            'provider_name' => $providerName,
            'is_valid' => false,
            'errors' => [],
            'warnings' => [],
            'config_sources' => [],
            'provider_class_valid' => false,
            'interface_compliance' => false,
        ];

        try {
            // 1. Check if provider is supported
            if (!in_array($providerName, $this->configService->getSupportedProviders())) {
                $result['errors'][] = "Provider '{$providerName}' is not supported";
                return $result;
            }

            // 2. Get and validate configuration
            $config = $this->configService->getEffectiveConfig($providerName);
            $configErrors = $this->configService->validateProviderConfig($providerName, $config);

            if (!empty($configErrors)) {
                $result['errors'] = array_merge($result['errors'], $configErrors);
            }

            // 3. Check configuration sources
            $schema = $this->configService->getProviderSchema($providerName);
            $allKeys = array_merge($schema['required'], $schema['optional']);
            
            foreach ($allKeys as $key) {
                if (isset($config[$key])) {
                    $source = $this->configService->getConfigSource($providerName, $key);
                    $result['config_sources'][$key] = $source;
                    
                    // Warn about missing environment variables for sensitive data
                    if (in_array($key, $schema['encrypted']) && $source !== 'environment') {
                        $result['warnings'][] = "Sensitive key '{$key}' should be set via environment variable for security";
                    }
                }
            }

            // 4. Validate provider class exists and implements interface
            try {
                $providerInstance = $this->factory->create($providerName, $config);
                $result['provider_class_valid'] = true;
                
                if ($providerInstance instanceof CloudStorageProviderInterface) {
                    $result['interface_compliance'] = true;
                } else {
                    $result['errors'][] = "Provider class does not implement CloudStorageProviderInterface";
                }
            } catch (\Exception $e) {
                $result['errors'][] = "Failed to instantiate provider: {$e->getMessage()}";
            }

            // 5. Check if provider is enabled
            $providerConfig = config("cloud-storage.providers.{$providerName}");
            if (isset($providerConfig['enabled']) && !$providerConfig['enabled']) {
                $result['warnings'][] = "Provider is disabled in configuration";
            }

            // 6. Validate provider-specific requirements
            $providerSpecificErrors = $this->validateProviderSpecificRequirements($providerName, $config);
            $result['errors'] = array_merge($result['errors'], $providerSpecificErrors);

            // 7. Determine overall validity
            $result['is_valid'] = empty($result['errors']) && $result['provider_class_valid'] && $result['interface_compliance'];

        } catch (\Exception $e) {
            $result['errors'][] = "Validation failed with exception: {$e->getMessage()}";
            Log::error('Provider configuration validation exception', [
                'provider' => $providerName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $result;
    }

    /**
     * Validate provider-specific requirements
     */
    private function validateProviderSpecificRequirements(string $providerName, array $config): array
    {
        $errors = [];

        switch ($providerName) {
            case 'google-drive':
                // Check OAuth configuration
                if (empty($config['client_id'])) {
                    $errors[] = 'Google Drive client ID is required';
                }
                if (empty($config['client_secret'])) {
                    $errors[] = 'Google Drive client secret is required';
                }
                
                // Validate redirect URI format
                if (isset($config['redirect_uri']) && !filter_var($config['redirect_uri'], FILTER_VALIDATE_URL)) {
                    $errors[] = 'Google Drive redirect URI must be a valid URL';
                }
                break;

            case 'amazon-s3':
                // Check AWS credentials
                if (empty($config['access_key_id'])) {
                    $errors[] = 'AWS access key ID is required';
                }
                if (empty($config['secret_access_key'])) {
                    $errors[] = 'AWS secret access key is required';
                }
                if (empty($config['region'])) {
                    $errors[] = 'AWS region is required';
                }
                if (empty($config['bucket'])) {
                    $errors[] = 'S3 bucket name is required';
                }

                // Validate region format
                if (isset($config['region']) && !preg_match('/^[a-z0-9-]+$/', $config['region'])) {
                    $errors[] = 'Invalid AWS region format';
                }

                // Validate bucket name format
                if (isset($config['bucket']) && !preg_match('/^[a-z0-9.-]+$/', $config['bucket'])) {
                    $errors[] = 'Invalid S3 bucket name format';
                }
                break;

            case 'azure-blob':
                // Check Azure connection string or account credentials
                if (empty($config['connection_string']) && (empty($config['account_name']) || empty($config['account_key']))) {
                    $errors[] = 'Azure connection string or account name/key is required';
                }
                if (empty($config['container'])) {
                    $errors[] = 'Azure container name is required';
                }

                // Validate connection string format
                if (isset($config['connection_string']) && !str_contains($config['connection_string'], 'AccountName=')) {
                    $errors[] = 'Invalid Azure connection string format';
                }
                break;

            case 'microsoft-teams':
                // Check OAuth configuration
                if (empty($config['client_id'])) {
                    $errors[] = 'Microsoft Teams client ID is required';
                }
                if (empty($config['client_secret'])) {
                    $errors[] = 'Microsoft Teams client secret is required';
                }
                break;

            case 'dropbox':
                // Check OAuth configuration
                if (empty($config['app_key'])) {
                    $errors[] = 'Dropbox app key is required';
                }
                if (empty($config['app_secret'])) {
                    $errors[] = 'Dropbox app secret is required';
                }
                break;
        }

        return $errors;
    }

    /**
     * Get configuration validation summary
     */
    public function getValidationSummary(): array
    {
        $results = $this->validateAllProviderConfigurations();
        
        return [
            'overall_status' => $results['summary']['invalid_count'] === 0 ? 'valid' : 'invalid',
            'total_providers' => $results['summary']['total_providers'],
            'valid_providers' => array_keys($results['valid']),
            'invalid_providers' => array_keys($results['invalid']),
            'providers_with_warnings' => array_keys($results['warnings']),
            'validation_timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Check if any providers are properly configured
     */
    public function hasValidProviders(): bool
    {
        $results = $this->validateAllProviderConfigurations();
        return $results['summary']['valid_count'] > 0;
    }

    /**
     * Get the first valid provider name
     */
    public function getFirstValidProvider(): ?string
    {
        $results = $this->validateAllProviderConfigurations();
        $validProviders = array_keys($results['valid']);
        
        return !empty($validProviders) ? $validProviders[0] : null;
    }

    /**
     * Validate configuration and log results
     */
    public function validateAndLog(): array
    {
        $results = $this->validateAllProviderConfigurations();
        
        // Log summary
        if ($results['summary']['invalid_count'] > 0) {
            Log::warning('Some cloud storage providers have invalid configurations', [
                'invalid_providers' => array_keys($results['invalid']),
                'valid_providers' => array_keys($results['valid']),
            ]);
        } else {
            Log::info('All configured cloud storage providers have valid configurations', [
                'valid_providers' => array_keys($results['valid']),
            ]);
        }

        // Log specific errors
        foreach ($results['invalid'] as $provider => $validation) {
            Log::error("Provider '{$provider}' has configuration errors", [
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings'],
            ]);
        }

        // Log warnings
        foreach ($results['warnings'] as $provider => $warnings) {
            Log::warning("Provider '{$provider}' has configuration warnings", [
                'warnings' => $warnings,
            ]);
        }

        return $results;
    }
}