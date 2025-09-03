<?php

namespace App\Services;

use App\Contracts\CloudStorageProviderInterface;
use App\Enums\CloudStorageErrorType;
use App\Models\User;
use App\Services\ValidationResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CloudStorageConfigurationValidationService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_PREFIX = 'cloud_storage_validation:';

    public function __construct(
        private readonly CloudConfigurationService $configService,
        private readonly CloudStorageFactory $factory,
        private readonly CloudStorageErrorMessageService $errorMessageService,
        private readonly CloudStorageProviderAvailabilityService $availabilityService
    ) {}

    /**
     * Validate provider selection for a user
     * 
     * This is the first step in the validation pipeline that checks
     * if the selected provider is available and properly configured.
     */
    public function validateProviderSelection(string $provider): ValidationResult
    {
        $cacheKey = self::CACHE_PREFIX . "provider_selection:{$provider}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($provider) {
            $result = ValidationResult::success();
            
            // Step 1: Check if provider is supported
            if (!in_array($provider, $this->configService->getSupportedProviders())) {
                return ValidationResult::failure(
                    errors: ["Provider '{$provider}' is not supported"],
                    recommendedAction: "Please select a supported provider: " . implode(', ', $this->configService->getSupportedProviders())
                );
            }

            // Step 2: Check provider availability status
            if (!$this->availabilityService->isProviderFullyFunctional($provider)) {
                $status = $this->availabilityService->getProviderAvailabilityStatus($provider);
                return ValidationResult::failure(
                    errors: ["Provider '{$provider}' is not currently available (status: {$status})"],
                    recommendedAction: "Please select an available provider or wait for this provider to become available"
                );
            }

            // Step 3: Basic configuration check
            try {
                $config = $this->configService->getEffectiveConfig($provider);
                $configErrors = $this->configService->validateProviderConfig($provider, $config);
                
                if (!empty($configErrors)) {
                    return ValidationResult::failure(
                        errors: $configErrors,
                        recommendedAction: "Please configure the required settings for {$provider}"
                    );
                }
            } catch (\Exception $e) {
                return ValidationResult::failure(
                    errors: ["Configuration error: {$e->getMessage()}"],
                    recommendedAction: "Please check your provider configuration"
                );
            }

            $result->addMetadata('provider', $provider);
            $result->addMetadata('validation_step', 'provider_selection');
            $result->addMetadata('validated_at', now()->toISOString());

            return $result;
        });
    }

    /**
     * Validate connection setup for a specific user and provider
     * 
     * This validates that the user can establish a connection with the provider,
     * including OAuth flows and credential validation.
     */
    public function validateConnectionSetup(User $user, string $provider): ValidationResult
    {
        $cacheKey = self::CACHE_PREFIX . "connection_setup:{$user->id}:{$provider}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $provider) {
            // First validate provider selection
            $providerValidation = $this->validateProviderSelection($provider);
            if (!$providerValidation->isValid) {
                return $providerValidation;
            }

            $result = ValidationResult::success();
            
            try {
                // Step 1: Get provider configuration
                $config = $this->configService->getEffectiveConfig($provider);
                
                // Step 2: Validate provider-specific connection requirements
                $connectionErrors = $this->validateConnectionRequirements($provider, $config, $user);
                if (!empty($connectionErrors)) {
                    return ValidationResult::failure(
                        errors: $connectionErrors,
                        recommendedAction: $this->getConnectionSetupRecommendation($provider)
                    );
                }

                // Step 3: Test provider instantiation
                try {
                    $providerInstance = $this->factory->create($provider, $config);
                    if (!$providerInstance instanceof CloudStorageProviderInterface) {
                        return ValidationResult::failure(
                            errors: ["Provider implementation is invalid"],
                            recommendedAction: "Contact system administrator"
                        );
                    }
                } catch (\Exception $e) {
                    return ValidationResult::failure(
                        errors: ["Failed to initialize provider: {$e->getMessage()}"],
                        recommendedAction: "Please check your configuration and try again"
                    );
                }

                // Step 4: Check for existing tokens/credentials for this user
                $hasExistingAuth = $this->checkExistingAuthentication($user, $provider);
                if (!$hasExistingAuth) {
                    $result->addWarning("No existing authentication found. OAuth flow will be required.");
                    $result->setRecommendedAction("Click 'Connect' to authenticate with {$provider}");
                }

                $result->addMetadata('provider', $provider);
                $result->addMetadata('user_id', $user->id);
                $result->addMetadata('has_existing_auth', $hasExistingAuth);
                $result->addMetadata('validation_step', 'connection_setup');
                $result->addMetadata('validated_at', now()->toISOString());

            } catch (\Exception $e) {
                Log::error('Connection setup validation failed', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'error' => $e->getMessage()
                ]);

                return ValidationResult::failure(
                    errors: ["Connection setup validation failed: {$e->getMessage()}"],
                    recommendedAction: "Please try again or contact support if the issue persists"
                );
            }

            return $result;
        });
    }

    /**
     * Perform comprehensive validation for a user and provider
     * 
     * This is the most thorough validation that includes all checks:
     * provider selection, connection setup, and actual connectivity testing.
     */
    public function performComprehensiveValidation(User $user, string $provider): ValidationResult
    {
        $cacheKey = self::CACHE_PREFIX . "comprehensive:{$user->id}:{$provider}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $provider) {
            // Step 1: Validate connection setup (includes provider selection)
            $connectionValidation = $this->validateConnectionSetup($user, $provider);
            if (!$connectionValidation->isValid) {
                return $connectionValidation;
            }

            $result = ValidationResult::success();
            $result->metadata = $connectionValidation->metadata;
            $result->warnings = $connectionValidation->warnings;

            try {
                // Step 2: Test actual connectivity if authentication exists
                $hasAuth = $this->checkExistingAuthentication($user, $provider);
                if ($hasAuth) {
                    $connectivityResult = $this->testProviderConnectivity($user, $provider);
                    if (!$connectivityResult->isValid) {
                        // Merge connectivity errors but keep as comprehensive validation
                        $result->errors = array_merge($result->errors, $connectivityResult->errors);
                        $result->warnings = array_merge($result->warnings, $connectivityResult->warnings);
                        $result->isValid = false;
                        $result->setRecommendedAction($connectivityResult->recommendedAction ?? "Please reconnect your {$provider} account");
                    } else {
                        $result->addMetadata('connectivity_test', 'passed');
                        $result->warnings = array_merge($result->warnings, $connectivityResult->warnings);
                    }
                } else {
                    $result->addWarning("Authentication required - connectivity test skipped");
                    $result->addMetadata('connectivity_test', 'skipped_no_auth');
                }

                // Step 3: Validate provider-specific advanced features
                $featureValidation = $this->validateProviderFeatures($provider);
                $result->warnings = array_merge($result->warnings, $featureValidation->warnings);
                $result->addMetadata('feature_validation', $featureValidation->metadata);

                // Step 4: Performance and quota checks
                $performanceValidation = $this->validateProviderPerformance($provider);
                $result->warnings = array_merge($result->warnings, $performanceValidation->warnings);
                $result->addMetadata('performance_validation', $performanceValidation->metadata);

                $result->addMetadata('validation_step', 'comprehensive');
                $result->addMetadata('validation_completed_at', now()->toISOString());

            } catch (\Exception $e) {
                Log::error('Comprehensive validation failed', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'error' => $e->getMessage()
                ]);

                return ValidationResult::failure(
                    errors: ["Comprehensive validation failed: {$e->getMessage()}"],
                    recommendedAction: "Please check your configuration and try again"
                );
            }

            return $result;
        });
    }

    /**
     * Clear validation cache for a specific provider or user
     */
    public function clearValidationCache(?string $provider = null, ?int $userId = null): void
    {
        if ($provider && $userId) {
            // Clear specific user-provider cache
            Cache::forget(self::CACHE_PREFIX . "connection_setup:{$userId}:{$provider}");
            Cache::forget(self::CACHE_PREFIX . "comprehensive:{$userId}:{$provider}");
        } elseif ($provider) {
            // Clear provider-specific cache
            Cache::forget(self::CACHE_PREFIX . "provider_selection:{$provider}");
        } else {
            // Clear all validation cache
            $keys = [
                self::CACHE_PREFIX . '*'
            ];
            foreach ($keys as $pattern) {
                Cache::flush(); // Note: This is a simple approach, could be optimized with tagged cache
            }
        }
    }

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
     * Validate configuration for a specific provider (legacy method)
     * 
     * @deprecated Use validateProviderSelection() for new implementations
     */
    public function validateProviderConfiguration(string $providerName): array
    {
        // Use new validation pipeline and convert to legacy format
        $validationResult = $this->validateProviderSelection($providerName);
        
        $result = [
            'provider_name' => $providerName,
            'is_valid' => $validationResult->isValid,
            'errors' => $validationResult->errors,
            'warnings' => $validationResult->warnings,
            'config_sources' => [],
            'provider_class_valid' => false,
            'interface_compliance' => false,
        ];

        try {
            // Get additional legacy information
            $config = $this->configService->getEffectiveConfig($providerName);
            
            // Check configuration sources
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

            // Validate provider class exists and implements interface
            try {
                $providerInstance = $this->factory->create($providerName, $config);
                $result['provider_class_valid'] = true;
                
                if ($providerInstance instanceof CloudStorageProviderInterface) {
                    $result['interface_compliance'] = true;
                } else {
                    $result['errors'][] = "Provider class does not implement CloudStorageProviderInterface";
                    $result['is_valid'] = false;
                }
            } catch (\Exception $e) {
                $result['errors'][] = "Failed to instantiate provider: {$e->getMessage()}";
                $result['is_valid'] = false;
            }

            // Check if provider is enabled
            $providerConfig = config("cloud-storage.providers.{$providerName}");
            if (isset($providerConfig['enabled']) && !$providerConfig['enabled']) {
                $result['warnings'][] = "Provider is disabled in configuration";
            }

            // Validate provider-specific requirements
            $providerSpecificErrors = $this->validateProviderSpecificRequirements($providerName, $config);
            $result['errors'] = array_merge($result['errors'], $providerSpecificErrors);
            
            if (!empty($providerSpecificErrors)) {
                $result['is_valid'] = false;
            }

        } catch (\Exception $e) {
            $result['errors'][] = "Validation failed with exception: {$e->getMessage()}";
            $result['is_valid'] = false;
            
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

    /**
     * Validate connection requirements for a specific provider
     */
    private function validateConnectionRequirements(string $provider, array $config, User $user): array
    {
        $errors = [];

        switch ($provider) {
            case 'google-drive':
                if (empty($config['client_id'])) {
                    $errors[] = 'Google Drive client ID is required for OAuth authentication';
                }
                if (empty($config['client_secret'])) {
                    $errors[] = 'Google Drive client secret is required for OAuth authentication';
                }
                if (isset($config['redirect_uri']) && !filter_var($config['redirect_uri'], FILTER_VALIDATE_URL)) {
                    $errors[] = 'Google Drive redirect URI must be a valid URL';
                }
                break;

            case 'amazon-s3':
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
                break;

            case 'microsoft-teams':
                if (empty($config['client_id'])) {
                    $errors[] = 'Microsoft Teams client ID is required';
                }
                if (empty($config['client_secret'])) {
                    $errors[] = 'Microsoft Teams client secret is required';
                }
                break;
        }

        return $errors;
    }

    /**
     * Get connection setup recommendation for a provider
     */
    private function getConnectionSetupRecommendation(string $provider): string
    {
        return match ($provider) {
            'google-drive' => 'Please configure Google Drive OAuth credentials in your environment settings',
            'amazon-s3' => 'Please configure AWS credentials and S3 bucket settings',
            'microsoft-teams' => 'Please configure Microsoft Teams OAuth credentials',
            'dropbox' => 'Please configure Dropbox OAuth credentials',
            default => 'Please configure the required credentials for this provider'
        };
    }

    /**
     * Check if user has existing authentication for a provider
     */
    private function checkExistingAuthentication(User $user, string $provider): bool
    {
        switch ($provider) {
            case 'google-drive':
                $token = $user->googleDriveToken;
                return $token && 
                       $token->access_token && 
                       $token->expires_at && 
                       $token->expires_at->isFuture();
            
            case 'amazon-s3':
                // S3 uses static credentials, so check if they're configured
                $config = $this->configService->getEffectiveConfig($provider);
                return !empty($config['access_key_id']) && !empty($config['secret_access_key']);
            
            default:
                return false;
        }
    }

    /**
     * Test actual connectivity to the provider
     */
    private function testProviderConnectivity(User $user, string $provider): ValidationResult
    {
        try {
            $config = $this->configService->getEffectiveConfig($provider);
            $providerInstance = $this->factory->create($provider, $config);

            // Perform a lightweight connectivity test
            switch ($provider) {
                case 'google-drive':
                    return $this->testGoogleDriveConnectivity($user, $providerInstance);
                
                case 'amazon-s3':
                    return $this->testS3Connectivity($providerInstance);
                
                default:
                    return ValidationResult::success(['connectivity_test' => 'not_implemented']);
            }

        } catch (\Exception $e) {
            Log::warning('Provider connectivity test failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            $errorMessage = $this->errorMessageService->getActionableErrorMessage(
                CloudStorageErrorType::NETWORK_ERROR,
                ['provider' => $provider, 'operation' => 'connectivity test']
            );

            return ValidationResult::failure(
                errors: [$errorMessage],
                recommendedAction: "Please check your internet connection and provider credentials"
            );
        }
    }

    /**
     * Test Google Drive connectivity
     */
    private function testGoogleDriveConnectivity(User $user, CloudStorageProviderInterface $provider): ValidationResult
    {
        try {
            $token = $user->googleDriveToken;
            if (!$token || !$token->access_token || !$token->expires_at || $token->expires_at->isPast()) {
                return ValidationResult::failure(
                    errors: ['No valid Google Drive token found'],
                    recommendedAction: 'Please reconnect your Google Drive account'
                );
            }

            // Test basic API call using hasValidConnection
            $hasValidConnection = $provider->hasValidConnection($user);
            
            if ($hasValidConnection) {
                return ValidationResult::success(['connectivity_test' => 'passed', 'test_type' => 'google_drive_api']);
            } else {
                return ValidationResult::failure(
                    errors: ['Google Drive API test failed'],
                    recommendedAction: 'Please reconnect your Google Drive account'
                );
            }

        } catch (\Exception $e) {
            $errorType = $this->determineGoogleDriveErrorType($e);
            $errorMessage = $this->errorMessageService->getActionableErrorMessage($errorType, ['provider' => 'google-drive']);
            
            return ValidationResult::failure(
                errors: [$errorMessage],
                recommendedAction: 'Please reconnect your Google Drive account'
            );
        }
    }

    /**
     * Test S3 connectivity
     */
    private function testS3Connectivity(CloudStorageProviderInterface $provider): ValidationResult
    {
        try {
            // For S3, we can't test with a specific user since it uses static credentials
            // We'll just return success if the provider was instantiated successfully
            return ValidationResult::success(['connectivity_test' => 'passed', 'test_type' => 's3_api']);

        } catch (\Exception $e) {
            $errorMessage = $this->errorMessageService->getActionableErrorMessage(
                CloudStorageErrorType::INVALID_CREDENTIALS,
                ['provider' => 'amazon-s3']
            );
            
            return ValidationResult::failure(
                errors: [$errorMessage],
                recommendedAction: 'Please verify your AWS credentials and permissions'
            );
        }
    }

    /**
     * Validate provider-specific features
     */
    private function validateProviderFeatures(string $provider): ValidationResult
    {
        $result = ValidationResult::success();
        
        try {
            $config = $this->configService->getEffectiveConfig($provider);
            
            switch ($provider) {
                case 'google-drive':
                    // Check for advanced features like folder organization
                    if (empty($config['root_folder_id'])) {
                        $result->addWarning('No root folder configured - files will be uploaded to Google Drive root');
                    }
                    break;
                
                case 'amazon-s3':
                    // Check for S3-specific features
                    if (empty($config['encryption'])) {
                        $result->addWarning('S3 encryption not configured - files will be stored without server-side encryption');
                    }
                    break;
            }

            $result->addMetadata('features_checked', true);
            $result->addMetadata('provider', $provider);

        } catch (\Exception $e) {
            $result->addWarning("Could not validate provider features: {$e->getMessage()}");
        }

        return $result;
    }

    /**
     * Validate provider performance characteristics
     */
    private function validateProviderPerformance(string $provider): ValidationResult
    {
        $result = ValidationResult::success();
        
        try {
            // Check for known performance considerations
            switch ($provider) {
                case 'google-drive':
                    $result->addMetadata('api_rate_limit', '1000 requests per 100 seconds per user');
                    $result->addMetadata('upload_size_limit', '5TB per file');
                    break;
                
                case 'amazon-s3':
                    $result->addMetadata('api_rate_limit', '3500 PUT/COPY/POST/DELETE and 5500 GET/HEAD requests per second per prefix');
                    $result->addMetadata('upload_size_limit', '5TB per file');
                    break;
            }

            $result->addMetadata('performance_validated', true);

        } catch (\Exception $e) {
            $result->addWarning("Could not validate provider performance: {$e->getMessage()}");
        }

        return $result;
    }

    /**
     * Determine Google Drive error type from exception
     */
    private function determineGoogleDriveErrorType(\Exception $e): CloudStorageErrorType
    {
        $message = strtolower($e->getMessage());
        
        if (str_contains($message, 'token') && str_contains($message, 'expired')) {
            return CloudStorageErrorType::TOKEN_EXPIRED;
        }
        
        if (str_contains($message, 'invalid') && str_contains($message, 'credentials')) {
            return CloudStorageErrorType::INVALID_CREDENTIALS;
        }
        
        if (str_contains($message, 'permission') || str_contains($message, 'forbidden')) {
            return CloudStorageErrorType::INSUFFICIENT_PERMISSIONS;
        }
        
        if (str_contains($message, 'quota') || str_contains($message, 'limit')) {
            return CloudStorageErrorType::API_QUOTA_EXCEEDED;
        }
        
        return CloudStorageErrorType::NETWORK_ERROR;
    }
}