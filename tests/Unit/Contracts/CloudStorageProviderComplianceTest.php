<?php

namespace Tests\Unit\Contracts;

use App\Contracts\CloudStorageProviderInterface;
use App\Services\GoogleDriveProvider;
use App\Services\S3Provider;
use Tests\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Test class to verify that all provider implementations comply with the interface.
 * 
 * This test ensures that all providers implement the required methods with
 * correct signatures and return types.
 */
class CloudStorageProviderComplianceTest extends TestCase
{
    /**
     * Get all provider classes that should implement the interface.
     */
    protected function getProviderClasses(): array
    {
        return [
            GoogleDriveProvider::class,
            S3Provider::class,
            // Add new providers here as they are implemented
        ];
    }

    /**
     * Test that all providers implement the CloudStorageProviderInterface.
     */
    public function test_all_providers_implement_interface(): void
    {
        foreach ($this->getProviderClasses() as $providerClass) {
            $reflection = new ReflectionClass($providerClass);
            $this->assertTrue(
                $reflection->implementsInterface(CloudStorageProviderInterface::class),
                "Provider {$providerClass} must implement CloudStorageProviderInterface"
            );
        }
    }

    /**
     * Test that all providers implement required methods with correct signatures.
     */
    public function test_all_providers_have_required_methods(): void
    {
        $interfaceReflection = new ReflectionClass(CloudStorageProviderInterface::class);
        $requiredMethods = $interfaceReflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($this->getProviderClasses() as $providerClass) {
            $providerReflection = new ReflectionClass($providerClass);

            foreach ($requiredMethods as $method) {
                $this->assertTrue(
                    $providerReflection->hasMethod($method->getName()),
                    "Provider {$providerClass} must implement method {$method->getName()}"
                );

                $providerMethod = $providerReflection->getMethod($method->getName());
                
                // Check parameter count
                $this->assertEquals(
                    $method->getNumberOfParameters(),
                    $providerMethod->getNumberOfParameters(),
                    "Method {$method->getName()} in {$providerClass} must have the same number of parameters as interface"
                );

                // Check parameter types
                $interfaceParams = $method->getParameters();
                $providerParams = $providerMethod->getParameters();

                for ($i = 0; $i < count($interfaceParams); $i++) {
                    $interfaceParam = $interfaceParams[$i];
                    $providerParam = $providerParams[$i];

                    $this->assertEquals(
                        $interfaceParam->getName(),
                        $providerParam->getName(),
                        "Parameter {$i} name mismatch in {$providerClass}::{$method->getName()}"
                    );

                    // Check parameter types if they exist
                    if ($interfaceParam->hasType() && $providerParam->hasType()) {
                        $this->assertEquals(
                            $interfaceParam->getType()->__toString(),
                            $providerParam->getType()->__toString(),
                            "Parameter {$i} type mismatch in {$providerClass}::{$method->getName()}"
                        );
                    }
                }

                // Check return types if they exist
                if ($method->hasReturnType() && $providerMethod->hasReturnType()) {
                    $this->assertEquals(
                        $method->getReturnType()->__toString(),
                        $providerMethod->getReturnType()->__toString(),
                        "Return type mismatch in {$providerClass}::{$method->getName()}"
                    );
                }
            }
        }
    }

    /**
     * Test that all providers can be instantiated without errors.
     */
    public function test_all_providers_can_be_instantiated(): void
    {
        foreach ($this->getProviderClasses() as $providerClass) {
            try {
                // Try to create instance through service container
                $provider = app($providerClass);
                $this->assertInstanceOf($providerClass, $provider);
                $this->assertInstanceOf(CloudStorageProviderInterface::class, $provider);
            } catch (\Exception $e) {
                $this->fail("Provider {$providerClass} could not be instantiated: " . $e->getMessage());
            }
        }
    }

    /**
     * Test that all providers return consistent data types for interface methods.
     */
    public function test_all_providers_return_consistent_types(): void
    {
        foreach ($this->getProviderClasses() as $providerClass) {
            try {
                $provider = app($providerClass);

                // Test methods that should return specific types
                $this->assertIsString($provider->getProviderName());
                $this->assertIsArray($provider->getCapabilities());
                $this->assertIsString($provider->getAuthenticationType());
                $this->assertIsString($provider->getStorageModel());
                $this->assertIsInt($provider->getMaxFileSize());
                $this->assertIsArray($provider->getSupportedFileTypes());

                // Test validateConfiguration with empty array
                $validationResult = $provider->validateConfiguration([]);
                $this->assertIsArray($validationResult);

                // Test supportsFeature
                $this->assertIsBool($provider->supportsFeature('file_upload'));
                $this->assertIsBool($provider->supportsFeature('nonexistent_feature'));

            } catch (\Exception $e) {
                $this->fail("Provider {$providerClass} failed type consistency test: " . $e->getMessage());
            }
        }
    }

    /**
     * Test that all providers have valid authentication types.
     */
    public function test_all_providers_have_valid_auth_types(): void
    {
        $validAuthTypes = ['oauth', 'api_key', 'service_account', 'connection_string'];

        foreach ($this->getProviderClasses() as $providerClass) {
            try {
                $provider = app($providerClass);
                $authType = $provider->getAuthenticationType();
                
                $this->assertContains(
                    $authType,
                    $validAuthTypes,
                    "Provider {$providerClass} must return a valid authentication type"
                );
            } catch (\Exception $e) {
                $this->fail("Provider {$providerClass} failed auth type test: " . $e->getMessage());
            }
        }
    }

    /**
     * Test that all providers have valid storage models.
     */
    public function test_all_providers_have_valid_storage_models(): void
    {
        $validStorageModels = ['hierarchical', 'flat', 'hybrid'];

        foreach ($this->getProviderClasses() as $providerClass) {
            try {
                $provider = app($providerClass);
                $storageModel = $provider->getStorageModel();
                
                $this->assertContains(
                    $storageModel,
                    $validStorageModels,
                    "Provider {$providerClass} must return a valid storage model"
                );
            } catch (\Exception $e) {
                $this->fail("Provider {$providerClass} failed storage model test: " . $e->getMessage());
            }
        }
    }

    /**
     * Test that all providers have required capabilities.
     */
    public function test_all_providers_have_required_capabilities(): void
    {
        $requiredCapabilities = ['file_upload', 'file_delete'];

        foreach ($this->getProviderClasses() as $providerClass) {
            try {
                $provider = app($providerClass);
                $capabilities = $provider->getCapabilities();
                
                foreach ($requiredCapabilities as $capability) {
                    $this->assertArrayHasKey(
                        $capability,
                        $capabilities,
                        "Provider {$providerClass} must have capability '{$capability}'"
                    );
                    
                    $this->assertTrue(
                        $provider->supportsFeature($capability),
                        "Provider {$providerClass} must support feature '{$capability}'"
                    );
                }
            } catch (\Exception $e) {
                $this->fail("Provider {$providerClass} failed capabilities test: " . $e->getMessage());
            }
        }
    }

    /**
     * Test that all providers have positive max file sizes.
     */
    public function test_all_providers_have_positive_max_file_size(): void
    {
        foreach ($this->getProviderClasses() as $providerClass) {
            try {
                $provider = app($providerClass);
                $maxFileSize = $provider->getMaxFileSize();
                
                $this->assertGreaterThan(
                    0,
                    $maxFileSize,
                    "Provider {$providerClass} must have a positive max file size"
                );
            } catch (\Exception $e) {
                $this->fail("Provider {$providerClass} failed max file size test: " . $e->getMessage());
            }
        }
    }

    /**
     * Test that all providers have non-empty supported file types.
     */
    public function test_all_providers_have_supported_file_types(): void
    {
        foreach ($this->getProviderClasses() as $providerClass) {
            try {
                $provider = app($providerClass);
                $supportedTypes = $provider->getSupportedFileTypes();
                
                $this->assertNotEmpty(
                    $supportedTypes,
                    "Provider {$providerClass} must have at least one supported file type"
                );
                
                // All elements should be strings
                foreach ($supportedTypes as $type) {
                    $this->assertIsString($type);
                }
            } catch (\Exception $e) {
                $this->fail("Provider {$providerClass} failed supported file types test: " . $e->getMessage());
            }
        }
    }
}